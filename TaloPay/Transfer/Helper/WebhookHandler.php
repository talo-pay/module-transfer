<?php

namespace TaloPay\Transfer\Helper;

use Magento\Framework\Controller\Result\JsonFactory;

class WebhookHandler
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var \TaloPay\Transfer\Helper\WebHookHandlers\ChargePaid
     */
    protected $chargePaid;

    /**
     * TaloPay Helper
     *
     * @var TaloPay\Transfer\Helper\Data;
     */
    protected $_helperData;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    protected const LOG_NAME = 'webhook_handler';

    /**
     * WebhookHandler constructor.
     *
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \TaloPay\Transfer\Helper\WebhookHandlers\ChargePaid $chargePaid
     * @param \TaloPay\Transfer\Helper\Data $helper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \TaloPay\Transfer\Helper\WebhookHandlers\ChargePaid $chargePaid,
        \TaloPay\Transfer\Helper\Data $helper,
        JsonFactory $resultJsonFactory,
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->chargePaid = $chargePaid;
        $this->_helperData = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Check if the webhook payload is valid.
     *
     * @param array $jsonBody
     * @return boolean
     */
    public function isValidWebhookPayload($jsonBody)
    {
        if (!isset($jsonBody['paymentId'])
            ) {
            return false;
        }

        return true;
    }

    /**
     * Handle incoming webhook.
     *
     * @param string $body
     *
     * @return bool
     */
    public function handle($body)
    {
        try {
            $jsonBody = json_decode($body, true);

            if ($this->isValidWebhookPayload($jsonBody)) {
                $paymentId = $jsonBody['paymentId'];

                return $this->chargePaid->chargePaid($paymentId);
            }

            $this->_helperData->log(
                "TaloPay WebApi::ProcessWebhook Invalid Payload: " . $body,
                self::LOG_NAME
            );

            return ['error' => 'Invalid Payload', 'success' => null];
        } catch (\Exception $e) {
            $this->_helperData->log(
                __(
                    sprintf(
                        'Fail when interpreting webhook JSON: %s',
                        $e->getMessage()
                    )
                )
            );
            return [
                'error' => 'Fail when interpreting webhook JSON',
                'success' => null,
            ];
        }
    }
}
