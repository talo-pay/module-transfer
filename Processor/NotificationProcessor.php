<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Processor;

use Magento\Sales\Api\Data\OrderInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
use TaloPay\Transfer\Api\NotificationSenderInterface;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

readonly class NotificationProcessor implements PaymentProcessorInterface
{
    /**
     * @param NotificationSenderInterface $notificationSender
     */
    public function __construct(
        private NotificationSenderInterface $notificationSender,
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
        $this->notificationSender->notifyInvoices($order, $previousResult->getInvoicesToNotify());

        return $previousResult;
    }
}
