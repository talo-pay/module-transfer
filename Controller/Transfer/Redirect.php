<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Controller\Transfer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;

class Redirect implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->logger->debug('Redirect: ' . var_export([
                'params' => $this->request->getParams(),
                'post' => $this->request->getPost(),
            ], true));

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([]);
    }
}
