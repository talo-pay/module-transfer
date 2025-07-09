<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Controller\Transfer;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Model\Order\Email\Sender\InstructionSender;

class Start implements HttpGetActionInterface
{
    /**
     * @param LoggerInterface $logger
     * @param RedirectFactory $redirectFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $checkoutSession
     * @param ConfigInterface $config
     * @param ApiClientInterface $apiClient
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $messageManager
     * @param OrderSender $orderSender
     * @param InstructionSender $instructionSender
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RedirectFactory $redirectFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Session $checkoutSession,
        private readonly ConfigInterface $config,
        private readonly ApiClientInterface $apiClient,
        private readonly UrlInterface $urlBuilder,
        private readonly ManagerInterface $messageManager,
        private readonly OrderSender $orderSender,
        private readonly InstructionSender $instructionSender,
    ) {
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $orderId = $this->checkoutSession->getLastOrderId();
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl($this->urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]));

        $order = null;
        try {
            if (!$orderId) {
                throw new LocalizedException(__('No order id specified.'));
            }

            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            $data = $this->apiClient->getPayData($order);
            $state = $this->config->getOrderStatus();

            $redirectUrl = $data['payment_url'];
            if (!$this->config->mustRedirect()) {
                $redirectUrl = $this->urlBuilder->getUrl('checkout/onepage/success');
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastOrderId($order->getId());
            }

            $additionalInformation = $payment->getAdditionalInformation() ?? [];
            if (isset($additionalInformation[ConfigInterface::ORDER_ADDITIONAL_KEY])) {
                $this->sendEmailInstructions($order);
            }

            $payment->setAdditionalInformation([
                ConfigInterface::ORDER_ADDITIONAL_KEY => $data,
                ...$additionalInformation
            ]);
            $order->addCommentToStatusHistory(__('Customer is redirected to %1', $redirectUrl), $state);
            $this->orderRepository->save($order);

            $resultRedirect->setUrl($redirectUrl);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An internal error occurred. Please try again later or contact with support.'));
        } finally {
            if ($order !== null) {
                $this->orderSender->send($order);
                $this->instructionSender->send($order);
            }
        }

        return $resultRedirect;
    }

    private function sendEmailInstructions(\Magento\Sales\Api\Data\OrderInterface $order)
    {
    }
}
