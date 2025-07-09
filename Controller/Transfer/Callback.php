<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Controller\Transfer;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param Request $request
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     * @param ApiClientInterface $apiClient
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        private readonly Request $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ConfigInterface $config,
        private readonly ApiClientInterface $apiClient,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly TransactionFactory $transactionFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $bodyParams = $this->request->getBodyParams();
        $resultJson = $this->resultJsonFactory->create();

        try {
            if ($this->config->isDebugMode()) {
                $this->logger->info('Callback return to payment', ['bodyParams' => $bodyParams]);
            }
            if (!isset($bodyParams['paymentId'])) {
                throw new LocalizedException(__('Invalid payment data.'));
            }
            $paymentId = $bodyParams['paymentId'];
            $taloPayment = $this->apiClient->getPayment($paymentId);
            if (empty($taloPayment) || !isset($taloPayment['magento'])) {
                throw new LocalizedException(__('There was an error processing your payment.'));
            }
            $order = $this->orderRepository->get($taloPayment['magento']['order_id']);
            $payment = $order->getPayment();
            $paymentAdditionalInfo = $payment->getAdditionalInformation();
            if (!isset($paymentAdditionalInfo['talopay_transfer']) ||
                !isset($paymentAdditionalInfo['talopay_transfer']['id']) ||
                $paymentAdditionalInfo['talopay_transfer']['id'] !== $paymentId
            ) {
                throw new LocalizedException(__('Invalid payment information'));
            }

            if (!$order->getTotalDue()) {
                $this->logger->info('The Order has been paid');
                throw new LocalizedException(__('The Order has been paid'));
            }

            $status = strtolower($taloPayment['status']);
            $this->logger->info('Change status', ['status' => $status]);
            $additionalInfo = [];

            $isOk = in_array($status, [
                ApiClientInterface::STATUS_SUCCESS,
                ApiClientInterface::STATUS_OVERPAID,
            ]);
            $state = null;
            if ($isOk) {
                $additionalInfo[] = __('Payment has been accepted');
                $state = $this->config->getStatusPay();
            }
            $isUnderpaid = in_array($status, [
                ApiClientInterface::STATUS_UNDERPAID,
            ]);

            if (!$isOk && !$isUnderpaid) {
                $state = $this->config->getStatusRejected();
                $additionalInfo[] = __('Payment has been rejected');
            }

            $additionalInfo[] = __('Notification ID %1', $paymentId);
            if ($status === ApiClientInterface::STATUS_OVERPAID) {
                $additionalInfo[] = __('The order has been overpaid');
            }
            if ($status === ApiClientInterface::STATUS_UNDERPAID) {
                $additionalInfo[] = __('The order has been underpaid');
            }

            $order->setState(Order::STATE_PROCESSING)
                ->addCommentToStatusHistory(implode('<br/>', $additionalInfo), $state ?? false);
            $this->orderRepository->save($order);

            if ($isOk) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->pay();
                $invoice->save();

                $transaction = $this->transactionFactory->create();
                $transaction->addObject($invoice);
                $transaction->addObject($invoice->getOrder());
                $transaction->save();

                $this->invoiceSender->send($invoice);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
        return $resultJson->setData([]);
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
