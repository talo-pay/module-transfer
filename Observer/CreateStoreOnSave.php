<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class CreateStoreOnSave implements ObserverInterface
{
    /**
     * @param ConfigInterface $config
     * @param WriterInterface $writerConfig
     * @param ApiClientInterface $apiClient
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        readonly private ConfigInterface $config,
        readonly private WriterInterface $writerConfig,
        readonly private ApiClientInterface $apiClient,
        readonly private StoreManagerInterface $storeManager,
        readonly private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $storeId = $this->config->getTaloPayStoreId();
        $appId = $this->config->getTaloPayAppId();

        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $parsedUrl = parse_url($baseUrl);
        $hostname = $parsedUrl['host'] ?? null;
        if (!$hostname) {
            $this->logger->error('Invalid host from baseUrl', ['baseUrl' => $baseUrl]);
            return;
        }

        try {
            [$newAppId, $newStoreId] = $this->getStoreData($hostname, $baseUrl);
            $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
            if ($observer->getWebsite()) {
                $scope = ScopeInterface::SCOPE_WEBSITE;
                $scopeId = $observer->getWebsite();
            }
            if ($observer->getStore()) {
                $scope = ScopeInterface::SCOPE_STORE;
                $scopeId = $observer->getStore();
            }
            if ($newAppId !== $appId) {
                $this->writerConfig->save(implode('/', [
                    'payment',
                    ConfigInterface::PAYMENT_CODE,
                    ConfigInterface::XPATH_TALOPAY_APP_ID
                ]), $newAppId, $scope, $scopeId);
            }
            if ($newStoreId !== $storeId) {
                $this->writerConfig->save(implode('/', [
                    'payment',
                    ConfigInterface::PAYMENT_CODE,
                    ConfigInterface::XPATH_TALOPAY_STORE_ID
                ]), $newStoreId, $scope, $scopeId);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return;
        }
    }

    /**
     * @param $hostname
     * @param $baseUrl
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreData($hostname, $baseUrl)
    {
        $appId = ConfigInterface::APP_ID;
        $storeId = $hostname . '_' . $this->storeManager->getStore()->getId();

        $storeRes = $this->apiClient->createStore([
            'store_id' => $storeId,
            'app_id' => $appId,
            'store_name' => $hostname,
            'store_type' => 'magento',
            'store_url' => $baseUrl,
        ]);

        return [
            $storeRes['app_id'] ?? $appId,
            $storeRes['store_id'] ?? ''
        ];
    }
}
