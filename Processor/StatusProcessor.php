<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Processor;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

class StatusProcessor implements PaymentProcessorInterface
{
    /**
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param EncryptorInterface $encryptor
     * @param SerializerInterface $serializer
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly EncryptorInterface $encryptor,
        private readonly SerializerInterface $serializer,
        private readonly PriceHelper $priceHelper,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        OrderInterface $order,
        string $paymentId,
        array $taloPayment,
        ?PaymentProcessorResponseInterface $previousResult = null
    ): ?PaymentProcessorResponseInterface {
        /** @var PaymentProcessorResponseInterface $previousResult */

        $payment = $order->getPayment();
        $paymentAdditionalInfo = $payment->getAdditionalInformation();

        // region This block will avoid to notify multiple times until the Talo Response changes
        $lastHashResponse = $paymentAdditionalInfo[ConfigInterface::ORDER_ADDITIONAL_KEY]['lastHashResponse'] ?? '';
        $currentHashResponse = $this->encryptor->hash($this->serializer->serialize($taloPayment));
        if ($currentHashResponse === $lastHashResponse) {
            return $previousResult;
        }
        // endregion
        $paymentAdditionalInfo[ConfigInterface::ORDER_ADDITIONAL_KEY]['lastHashResponse'] = $currentHashResponse;

        $order->setState(Order::STATE_PENDING_PAYMENT);

        if (!$order->getTotalDue()) {
            $previousResult->addComment(__('Payment has been accepted'));
            $previousResult->setStatus($this->config->getStatusPay())
                ->setState(Order::STATE_PROCESSING);
            $paymentAdditionalInfo[ConfigInterface::ORDER_ADDITIONAL_KEY]['alreadyPaid'] = true;
        }

        if ($order->getTotalPaid() > $order->getGrandTotal()) {
            $previousResult->addComment(__('The order has been overpaid'));
        }

        if ($order->getTotalPaid() < $order->getGrandTotal()) {
            $previousResult->addComment(__(
                'Partial payment received. Remaining balance: %1',
                $this->priceHelper->currencyByStore($order->getTotalDue(), $order->getStoreId()),
            ));
        }

        if ($payment->getAdditionalInformation() !== $paymentAdditionalInfo) {
            $previousResult->setMustSave(true);
            $payment->setAdditionalInformation($paymentAdditionalInfo);
        }

        return $previousResult;
    }
}
