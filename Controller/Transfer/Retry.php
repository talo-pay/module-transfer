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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class Retry implements HttpGetActionInterface
{
    /**
     * @param RedirectFactory $redirectFactory
     * @param Session $checkoutSession
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        private readonly RedirectFactory $redirectFactory,
        private readonly Session $checkoutSession,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestInterface $request,
        private readonly ConfigInterface $config,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $key = $this->request->getParam('key');
        $orderId = (int)$this->request->getParam('order_id');

        $order = $this->orderRepository->get($orderId);
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setUrl($this->urlBuilder->getUrl('/'));

        if ($this->config->getOrderKey($order) === $key) {
            $this->checkoutSession->setLastOrderId($orderId);
            $resultRedirect->setUrl($this->urlBuilder->getUrl('talopay/transfer/start'));
        }

        return $resultRedirect;
    }
}
