<?php

namespace TaloPay\Transfer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class ChangeConfigModule implements ObserverInterface
{
    /**
     * Talo helper to access and set data
     *
     * @var \TaloPay\Transfer\Helper\Data
     */
    protected $_helperData;

    /**
     * Handler with store logic
     *
     * @var \TaloPay\Transfer\Helper\WebhookHandlers\StoreHandler
     */
    protected $_storeHandler;
    
    /**
     * ChangeConfigModule constructor.
     *
     * @param \TaloPay\Transfer\Helper\Data $helperData
     * @param \TaloPay\Transfer\Helper\WebhookHandlers\StoreHandler $storeHandler
     */
    public function __construct(
        \TaloPay\Transfer\Helper\Data $helperData,
        \TaloPay\Transfer\Helper\WebhookHandlers\StoreHandler $storeHandler
    ) {
        $this->_helperData = $helperData;
        $this->_storeHandler = $storeHandler;
    }
    
    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->_helperData->log(
            'ChangeConfigModule::execute - Changing config module',
            'change_config_module'
        );
        #if store_id and app_id are set just save new credential
        $storeId = $this->_helperData->getTaloPayStoreId('production');
        $appId = $this->_helperData->getTaloPayAppId('production');
        $storeIdSandbox = $this->_helperData->getTaloPayStoreId('sandbox');
        $appIdSandbox = $this->_helperData->getTaloPayAppId('sandbox');
        $this->_helperData->log(
            'ChangeConfigModule::execute - Store ID and App ID',
            'change_config_module',
            [
                'storeId' => $storeId,
                'appId' => $appId,
                'storeIdSandbox' => $storeIdSandbox,
                'appIdSandbox' => $appIdSandbox
            ]
        );
        if (!empty($storeId) && !empty($appId)) {
            $this->_helperData->log(
                'ChangeConfigModule::execute - Production Store ID and App ID are set',
                'change_config_module',
                [
                    'storeId' => $storeId,
                    'appId' => $appId
                ]
            );
        } else {
            $this->_helperData->log(
                'ChangeConfigModule::execute - Production Store ID and App ID are not set. Creating store',
                'change_config_module'
            );
            $storeCreated = $this->_storeHandler->createStore('production');
            if (!$storeCreated) {
                $this->_helperData->log(
                    'ChangeConfigModule::execute - Error creating production store',
                    'change_config_module'
                );
                
            }
        }

        if (!empty($storeIdSandbox) && !empty($appIdSandbox)) {
            $this->_helperData->log(
                'ChangeConfigModule::execute - Sandbox Store ID and App ID are set',
                'change_config_module',
                [
                    'storeId' => $storeIdSandbox,
                    'appId' => $appIdSandbox
                ]
            );
            return;
        } else if(!empty($this->_helperData->getTaloPayUserId('sandbox')) && !empty($this->_helperData->getTaloPayClientId('sandbox')) && !empty($this->_helperData->getTaloPayClientSecret('sandbox'))){
            $this->_helperData->log(
                'ChangeConfigModule::execute - Sandbox credentials are set. Creating store',
                'change_config_module'
            );
            $storeCreated = $this->_storeHandler->createStore('sandbox');
            if (!$storeCreated) {
                $this->_helperData->log(
                    'ChangeConfigModule::execute - Error creating sandbox store',
                    'change_config_module'
                );
            }
        }
        
    }
}
