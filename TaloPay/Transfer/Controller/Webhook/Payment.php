<?php
namespace TaloPay\Transfer\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use TaloPay\Transfer\Helper\WebhookHandler;
use TaloPay\Transfer\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;

class Payment extends Action
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var WebhookHandler
     */
    private $webhookHandler;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Payment constructor.
     *
     * @param WebhookHandler $webhookHandler
     * @param Context $context
     * @param Data $helperData
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        WebhookHandler $webhookHandler,
        Context $context,
        Data $helperData,
        JsonFactory $resultJsonFactory,
    ) {
        $this->helperData = $helperData;
        $this->webhookHandler = $webhookHandler;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Execute the webhook
     *
     * @return void
     */
    public function execute()
    {
        $this->helperData->log('Start webhook');
        $resultJson = $this->resultJsonFactory->create();
        $body = $this->getRequest()->getContent();
        $this->helperData->debugJson("Webhook New Event!", "debug event", \json_decode($body, true));
        $result = $this->webhookHandler->handle($body);

        $statusCode = isset($result['error']) && \strlen($result['error']) > 0 ? 400 : 200;

        $resultJson->setHttpResponseCode($statusCode);

        return $resultJson->setData($result);
    }
}
