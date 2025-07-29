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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param Request $request
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     * @param ApiClientInterface $apiClient
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param PaymentProcessorInterface $paymentProcessor
     */
    public function __construct(
        private readonly Request $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ConfigInterface $config,
        private readonly ApiClientInterface $apiClient,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
        private readonly PaymentProcessorInterface $paymentProcessor,
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
            $response = $this->paymentProcessor->execute($order, $paymentId, $taloPayment);
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
