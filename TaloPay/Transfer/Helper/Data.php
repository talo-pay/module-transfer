<?php

namespace TaloPay\Transfer\Helper;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Encryption\EncryptorInterface as encryptor;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TaloPay\Transfer\Logger\Logger;
use TaloPay\Transfer\Helper\TaloPayConfig;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class Data extends AbstractHelper
{
    /**
     * @var Logger
     */
    protected $_talopayLogger;

    /**
     * @var TaloPayConfig
     */
    protected $_talopayConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var RemoteAddress
     */
    protected $remoteAddress;

    /**
     * @var encryptor
     */
    protected $encryptor;

    /**
     * @var WriterInterface
     */
    protected $writerConfig;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var Customer
     */
    protected $customerRepo;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var SerializerInterface
     */
    protected $_encryptor;

    /**
     * @var WriterInterface
     */
    protected $_writerConfig;

    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager ,
     * @param Context $context
     * @param Logger $logger
     * @param Session $checkoutSession ,
     * @param Customer $customer ,
     * @param ProductMetadataInterface $productMetadata ,
     * @param ModuleListInterface $moduleList ,
     * @param Curl $curl ,
     * @param SerializerInterface $serializer ,
     * @param RemoteAddress $remoteAddress ,
     * @param encryptor $encryptor ,
     * @param WriterInterface $writerConfig ,
     * @param TypeListInterface $cacheTypeList ,
     * @param TaloPayConfig $taloPayConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        Logger $logger,
        Session $checkoutSession,
        Customer $customer,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        Curl $curl,
        SerializerInterface $serializer,
        RemoteAddress $remoteAddress,
        encryptor $encryptor,
        WriterInterface $writerConfig,
        TypeListInterface $cacheTypeList,
        TaloPayConfig $taloPayConfig
    ) {
        $this->storeManager = $storeManager;
        $this->_talopayLogger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->customerRepo = $customer;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->_curl = $curl;
        $this->serializer = $serializer;
        $this->remoteAddress = $remoteAddress;
        $this->_encryptor = $encryptor;
        $this->_writerConfig = $writerConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->_talopayConfig = $taloPayConfig;
        parent::__construct($context);
    }

    /**
     * Log custom message using TaloPay logger instance
     *
     * @param string $message
     * @param string $name
     * @param array|null $array
     */
    public function log($message, $name = 'talopay', $array = null)
    {
        if ($array != null) {
            $message .= ' - ' . json_encode($array);
        }
        $this->_talopayLogger->setName($name);
        $this->_talopayLogger->debug($message);
    }

    /**
     * Log custom message using TaloPay logger instance
     *
     * @param string $message
     * @param string $name
     * @param mixed $objectToBeEncoded
     * @return void
     */
    public function debugJson(
        $message,
        $name = 'talopay',
        $objectToBeEncoded = null
    ) {
        $jsonEncodedObject = json_encode(
            $objectToBeEncoded,
            JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_NUMERIC_CHECK |
                JSON_PRETTY_PRINT
        );
        $this->_talopayLogger->debug($message . "\n" . $jsonEncodedObject);
    }

    /**
     * Get TaloPay API URL
     *
     * @return string
     */
    public function getTaloPayApiUrl()
    {
        return $this->_talopayConfig->getTaloPayApiUrl();
    }

    /**
     * Get TaloPay platform url
     *
     * @return void
     */
    public function getTaloPayPlatformUrl()
    {
        return $this->_talopayConfig->getTaloPayPlatformUrl();
    }

    /**
     * Get TaloPay plugin url script
     *
     * @return string
     */
    public function getTaloPayPluginUrlScript(): string
    {
        return $this->_talopayConfig->getTaloPayPluginUrlScript();
    }

    /**
     * Check if the payment method is available
     *
     * @return boolean
     */
    public function isAvailable():bool
    {
        $clientId = $this->getConfig('payment/talopay_transfer/client_id');
        $clientSecret = $this->getConfig('payment/talopay_transfer/client_secret');
        $userId = $this->getConfig('payment/talopay_transfer/user_id');
        $storeId = $this->getConfig('payment/talopay_transfer/store_id');
        $appId = $this->getConfig('payment/talopay_transfer/app_id');
        $this->log('isAvailable', 'talopay', [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'userId' => $userId,
            'storeId' => $storeId,
            'appId' => $appId
        ]);
        return !empty($clientId) && !empty($clientSecret) && !empty($userId) && !empty($storeId) && !empty($appId);
    }

    /**
     * Get TaloPay enabled
     *
     * @return void
     */
    public function getTaloPayEnabled()
    {
        return $this->getConfig('payment/talopay_transfer/active');
    }

    /**
     * Get config
     *
     * @param string $path
     * @param boolean $clearCache
     * @return void
     */
    public function getConfig($path, $clearCache = false)
    {
        if ($clearCache) {
            $this->clearCache();
        }
        $storeScope = ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue($path, $storeScope);
    }

    /**
     * Get scope config
     *
     * @return void
     */
    public function getScopeConfig()
    {
        return ScopeInterface::SCOPE_STORE;
    }

    /**
     * Set config
     *
     * @param string $variable
     * @param string $value
     * @param boolean $clearCache
     * @return void
     */
    public function setConfig($variable, $value, $clearCache = false)
    {
        if ($clearCache) {
            $this->clearCache();
        }
        $path = 'payment/talopay_transfer/' . $variable;
        return $this->_writerConfig->save($path, $value);
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache()
    {
        $this->cacheTypeList->cleanType(
            \Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER
        );
    }

    /**
     * Get app id
     *
     * @param boolean $clearCache
     * @return void
     */
    public function getTaloPayAppId($clearCache = false)
    {
        return $this->getConfig('payment/talopay_transfer/app_id', $clearCache);
    }

    /**
     * Set app id
     *
     * @param string $appID
     * @param boolean $clearCache
     * @return void
     */
    public function setAppId($appID, $clearCache = false)
    {
        return $this->setConfig('app_id', $appID, $clearCache);
    }

    /**
     * Get store id
     *
     * @param boolean $clearCache
     * @return void
     */
    public function getTaloPayStoreId($clearCache = false)
    {
        return $this->getConfig('payment/talopay_transfer/store_id', $clearCache);
    }

    /**
     * Set store id
     *
     * @param string $storeId
     * @param boolean $clearCache
     * @return void
     */
    public function setStoreId($storeId, $clearCache = false)
    {
        return $this->setConfig('store_id', $storeId, $clearCache);
    }

    /**
     * Get client id
     *
     * @param boolean $clearCache
     * @return void
     */
    public function getTaloPayClientId($clearCache = false)
    {
        return $this->getConfig('payment/talopay_transfer/client_id', $clearCache);
    }

    /**
     * Set client id
     *
     * @param string $clientId
     * @param boolean $clearCache
     * @return void
     */
    public function setClientId($clientId, $clearCache = false)
    {
        return $this->setConfig('client_id', $clientId, $clearCache);
    }

    /**
     * Get client secret
     *
     * @param boolean $clearCache
     * @return void
     */
    public function getTaloPayClientSecret($clearCache = false)
    {
        return $this->getConfig('payment/talopay_transfer/client_secret', $clearCache);
    }

    /**
     * Set client secret
     *
     * @param string $clientSecret
     * @param boolean $clearCache
     * @return void
     */
    public function setClientSecret($clientSecret, $clearCache = false)
    {
        return $this->setConfig('client_secret', $clientSecret, $clearCache);
    }

    /**
     * Get user id
     *
     * @return void
     */
    public function getTaloPayUserId()
    {
        return $this->getConfig('payment/talopay_transfer/user_id');
    }

    /**
     * Set user id
     *
     * @param string $userId
     * @param boolean $clearCache
     * @return void
     */
    public function setUserId($userId, $clearCache = false)
    {
        return $this->setConfig('user_id', $userId, $clearCache);
    }
}
