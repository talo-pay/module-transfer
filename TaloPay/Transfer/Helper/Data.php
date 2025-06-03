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
     * @param string $env
     * @return string
     */
    public function getTaloPayApiUrl($env = 'production')
    {
        return $this->_talopayConfig->getTaloPayApiUrl($env);
    }

    /**
     * Get TaloPay platform url
     *
     * @param string $env
     * @return string
     */
    public function getTaloPayPlatformUrl($env = 'production')
    {
        return $this->_talopayConfig->getTaloPayPlatformUrl($env);
    }

    /**
     * Get TaloPay plugin url script
     *
     * @return string
     */
    public function getTaloPayPluginUrlScript(): string
    {
        $activeEnv = $this->getEnv(null);
        return $this->_talopayConfig->getTaloPayPluginUrlScript($activeEnv);
    }

    /**
     * Check if the payment method is available
     *
     * @return boolean
     */
    public function isAvailable():bool
    {
        $activeEnv = $this->getConfig('payment/talopay_transfer/talopay_sandbox_mode') === '1' ? 'sandbox' : 'production';

        $clientId = $this->getTaloPayClientId($activeEnv);
        $clientSecret = $this->getTaloPayClientSecret($activeEnv);
        $userId = $this->getTaloPayUserId($activeEnv);
        $storeId = $this->getTaloPayStoreId($activeEnv);
        $appId = $this->getTaloPayAppId($activeEnv);
        $this->log('isAvailable', 'talopay', [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'userId' => $userId,
            'storeId' => $storeId,
            'appId' => $appId,
            'activeEnv' => $activeEnv,
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
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function getTaloPayAppId($env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/app_id'
            : 'payment/talopay_transfer/app_id_sandbox';
        return $this->getConfig($path, $clearCache);
    }

    /**
     * Get env
     *
     * @param string $env
     * @return string
     */
    public function getEnv($env = null)
    {
        $isSandbox = $this->getConfig('payment/talopay_transfer/talopay_sandbox_mode');
        return $env ?? ($isSandbox === '1' ? 'sandbox' : 'production');
    }

    /**
     * Set app id
     *
     * @param string $appID
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function setAppId($appID, $env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'app_id'
            : 'app_id_sandbox';
        return $this->setConfig($path, $appID, $clearCache);
    }

    /**
     * Get store id
     *
     * @param string $env
     * @param boolean $clearCache
     * @return void
        */
    public function getTaloPayStoreId($env = null, $clearCache = false)
    {
        $this->log('getTaloPayStoreId', 'talopay', [
            'env' => $env,
            'clearCache' => $clearCache,
        ]);
        $activeEnv = $this->getEnv($env);
        $this->log('getTaloPayStoreId', 'talopay', [
            'env' => $env,
            'activeEnv' => $activeEnv,
        ]);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/store_id'
            : 'payment/talopay_transfer/store_id_sandbox';
        $this->log('getTaloPayStoreId', 'talopay', [
            'path' => $path,
            'activeEnv' => $activeEnv,
        ]);
        return $this->getConfig($path, $clearCache);
    }

    /**
     * Set store id
     *
     * @param string $storeId
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function setStoreId($storeId, $env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'store_id'
            : 'store_id_sandbox';
        return $this->setConfig($path, $storeId, $clearCache);
    }

    /**
     * Get TaloPay Client ID
     *
     * @param string $env
     * @param boolean $clearCache
     * @return string
     */
    public function getTaloPayClientId($env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/client_id'
            : 'payment/talopay_transfer/client_id_sandbox';
        return $this->getConfig($path, $clearCache);
    }

    /**
     * Set client id
     *
     * @param string $clientId
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function setClientId($clientId, $env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/client_id'
            : 'payment/talopay_transfer/client_id_sandbox';
        return $this->setConfig('client_id', $clientId, $clearCache);
    }

    /**
     * Get TaloPay Client Secret
     *
     * @param string $env
     * @param boolean $clearCache
     * @return string
     */
    public function getTaloPayClientSecret($env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/client_secret'
            : 'payment/talopay_transfer/client_secret_sandbox';
        return $this->getConfig($path, $clearCache);
    }

    /**
     * Set client secret
     *
     * @param string $clientSecret
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function setClientSecret($clientSecret, $env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'client_secret'
            : 'client_secret_sandbox';
        return $this->setConfig($path, $clientSecret, $clearCache);
    }

    /**
     * Get TaloPay User ID
     *
     * @param string $env
     * @return string
     */
    public function getTaloPayUserId($env = null)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'payment/talopay_transfer/user_id'
            : 'payment/talopay_transfer/user_id_sandbox';
        return $this->getConfig($path);
    }

    /**
     * Set user id
     *
     * @param string $userId
     * @param string $env
     * @param boolean $clearCache
     * @return void
     */
    public function setUserId($userId, $env = null, $clearCache = false)
    {
        $activeEnv = $this->getEnv($env);
        $path = $activeEnv === 'production'
            ? 'user_id'
            : 'user_id_sandbox';
        return $this->setConfig($path, $userId, $clearCache);
    }
}
