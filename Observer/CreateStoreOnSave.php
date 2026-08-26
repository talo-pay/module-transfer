<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Observer;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
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
     * @param ManagerInterface $messageManager
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        readonly private ConfigInterface $config,
        readonly private WriterInterface $writerConfig,
        readonly private ApiClientInterface $apiClient,
        readonly private StoreManagerInterface $storeManager,
        readonly private LoggerInterface $logger,
        readonly private ManagerInterface $messageManager,
        readonly private TypeListInterface $cacheTypeList,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if ($observer->getWebsite()) {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $observer->getWebsite();
        }
        if ($observer->getStore()) {
            return;
        }

        try {
            $store = $this->resolveStore($scope, (int)$scopeId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(
                __(
                    '[TaloPay Transfer] There was an error: %1',
                    $e->getMessage()
                )
            );
            return;
        }

        $storeId = $this->config->getTaloPayStoreId((int)$store->getId());
        $appId = $this->config->getTaloPayAppId((int)$store->getId());

        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $parsedUrl = parse_url($baseUrl);
        $hostname = $parsedUrl['host'] ?? null;
        if (!$hostname) {
            $this->logger->error('Invalid host from baseUrl', ['baseUrl' => $baseUrl]);
            return;
        }
        $prefix = '';
        if ($scope === ScopeInterface::SCOPE_WEBSITES) {
            $prefix = 'W';
        }

        $title = $this->config->getStoreName((int)$store->getId()) ?: $hostname;
        $isValueChanged = false;

        try {
            [$newAppId, $newStoreId] = $this->getStoreData($title, $hostname, $baseUrl, $store, $prefix);
            if (!$newStoreId) {
                throw new LocalizedException(__('The store was not created'));
            }
            if ($newAppId !== $appId) {
                $this->writerConfig->save(implode('/', [
                    'payment',
                    ConfigInterface::PAYMENT_CODE,
                    ConfigInterface::XPATH_TALOPAY_APP_ID
                ]), $newAppId, $scope, $scopeId);
                $isValueChanged = true;
            }
            if ($newStoreId !== $storeId) {
                $this->writerConfig->save(implode('/', [
                    'payment',
                    ConfigInterface::PAYMENT_CODE,
                    ConfigInterface::XPATH_TALOPAY_STORE_ID
                ]), $newStoreId, $scope, $scopeId);
                $isValueChanged = true;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(
                __(
                    '[TaloPay Transfer] There was an error: %1',
                    $e->getMessage()
                )
            );
            return;
        } finally {
            if ($isValueChanged) {
                $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
            }
        }
    }

    /**
     * Resolve the store whose base URL should be used for the given save-event scope.
     *
     * @param string $scope
     * @param int $scopeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     */
    private function resolveStore(string $scope, int $scopeId)
    {
        if ($scope === ScopeInterface::SCOPE_WEBSITES) {
            $defaultStore = $this->storeManager->getWebsite($scopeId)->getDefaultStore();
            if (!$defaultStore) {
                throw new LocalizedException(__('The website has no default store'));
            }
            return $defaultStore;
        }
        return $this->storeManager->getStore();
    }

    /**
     * @param string $title
     * @param string $hostname
     * @param string $baseUrl
     * @param StoreInterface $store
     * @param string $prefix
     * @return array
     */
    private function getStoreData($title, $hostname, $baseUrl, $store, $prefix = '')
    {
        $appId = ConfigInterface::APP_ID;
        $storeId = $hostname . '_' . $prefix . $store->getId();

        $storeRes = $this->apiClient->createStore([
            'store_id' => $storeId,
            'app_id' => $appId,
            'store_name' => $title,
            'store_type' => 'magento',
            'store_url' => $baseUrl,
        ]);

        return [
            $storeRes['app_id'] ?? $appId,
            $storeRes['store_id'] ?? ''
        ];
    }
}
