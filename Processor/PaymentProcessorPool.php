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
use Magento\Sales\Api\OrderRepositoryInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterfaceFactory;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

readonly class PaymentProcessorPool implements PaymentProcessorInterface
{
    /**
     * @param PaymentProcessorResponseInterfaceFactory $paymentProcessorResponseFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param array $handlers
     */
    public function __construct(
        private PaymentProcessorResponseInterfaceFactory $paymentProcessorResponseFactory,
        private OrderRepositoryInterface $orderRepository,
        private array $handlers = [],
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
        $previousResult = $previousResult ?? $this->paymentProcessorResponseFactory->create();
        foreach ($this->handlers as $handler) {
            $handler->execute($order, $paymentId, $taloPayment, $previousResult);
        }

        if ($previousResult->getStatus() ?? false) {
            $order->setStatus($previousResult->getStatus());
        }
        if ($previousResult->getState() ?? false) {
            $order->setState($previousResult->getState());
        }
        if ($previousResult->getComments()) {
            $order->addCommentToStatusHistory(implode('<br/>', $previousResult->getComments()));
        }
        if ($previousResult->mustSave()) {
            $this->orderRepository->save($order);
        }
        return $previousResult;
    }
}
