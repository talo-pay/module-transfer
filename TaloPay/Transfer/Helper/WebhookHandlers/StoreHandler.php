<?php

namespace TaloPay\Transfer\Helper\WebhookHandlers;

use TaloPay\Transfer\Api\TaloApiClient;
use TaloPay\Transfer\Helper\Data;

class StoreHandler
{
    /**
     * TaloPay API Client
     *
     * @var TaloApiClient
     */
    protected $_taloApiClient;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Helper to access and set data
     *
     * @var Data
     */
    protected $_helperData;

    protected const LOG_NAME = 'store_handler';

    /**
     * StoreHandler constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param TaloApiClient $apiClient
     * @param Data $helperData
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        TaloApiClient $apiClient,
        Data $helperData
    ) {
        $this->_taloApiClient = $apiClient;
        $this->_storeManager = $storeManager;
        $this->_helperData = $helperData;
    }

    /**
     * Create store in TaloPay
     *
     * @return void
     */
    public function createStore()
    {
        $this->_helperData->log(
            'StoreHandler::createStore - Creating store',
            self::LOG_NAME
        );
        
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $uri = \Laminas\Uri\UriFactory::factory($baseUrl);
        $hostname = $uri->getHost();
        $this->_helperData->log(
            'StoreHandler::createStore - Hostname',
            self::LOG_NAME,
            $hostname
        );

        $store_id = $hostname . '_' . $this->_storeManager->getStore()->getId();
        $storePayload = [
            'store_id' => $store_id,
            'app_id'=> 'magento_talopay_transfer',
            'store_name'=> $hostname,
            'store_type'=> 'magento',
            'store_url'=> $baseUrl,
        ];

        $this->_helperData->log(
            'StoreHandler::createStore - Store Payload',
            self::LOG_NAME,
            $storePayload
        );
        $storeRes = $this->_taloApiClient->createStore(
            $storePayload
        );

        if ($storeRes) {
            $this->_helperData->log(
                'StoreHandler::createStore - Store created',
                self::LOG_NAME,
                $storeRes
            );
            $this->_helperData->setStoreId($storeRes['store_id'], true);
            $this->_helperData->setAppId($storeRes['app_id'] ?? $storePayload['app_id'], true);
            return true;
        } else {
            return false;
        }
    }
}
