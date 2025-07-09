<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ApiClientInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Exception\ApiException;
use TaloPay\Transfer\Model\Cache\Type\AccessToken as AccessTokenCache;

class ApiClient implements ApiClientInterface
{
    /**
     * @param CurlFactory $curlFactory
     * @param ConfigInterface $config
     * @param SerializerInterface $serializer
     * @param AccessTokenCache $cache
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly ConfigInterface $config,
        private readonly SerializerInterface $serializer,
        private readonly AccessTokenCache $cache,
        private readonly LoggerInterface $logger,
        private readonly UrlInterface $urlBuilder,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createStore(array $array)
    {
        $curlClient = $this->getCurlClient();
        $curlClient->addHeader('Content-Type', 'application/json');
        $url = $this->getEndpointUrl(sprintf('/users/%s/stores', $this->config->getUserId()));

        $dataPost = $this->serializer->serialize($array);

        $body = '';
        try {
            $curlClient->post($url, $dataPost);
            $body = $curlClient->getBody();

            $response = $this->serializer->unserialize($body);
            if (isset($response['data'])) {
                return $response['data'];
            }
            throw new LocalizedException(__('Invalid response data'));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['body' => $body, 'request' => $dataPost]);

        }
        return null;
    }

    /**
     * Get Curl client
     *
     * @return Curl
     * @throws LocalizedException
     */
    protected function getCurlClient(): Curl
    {
        $curlClient = $this->curlFactory->create();
        $bearer = $this->getBearerToken();
        $curlClient->addHeader('Authorization', sprintf('Bearer %s', $bearer));
        return $curlClient;
    }

    /**
     * @param string|null $environment
     * @param string|null $userId
     * @param string|null $clientId
     * @param string|null $clientSecret
     * @return string
     * @throws LocalizedException
     */
    protected function getBearerToken(
        string $environment = null,
        string $userId = null,
        string $clientId = null,
        string $clientSecret = null
    ) {
        $curlClient = $this->curlFactory->create();
        $environment = $environment ?? $this->config->getEnvironment();
        $userId = $userId ?? $this->config->getUserId($environment);
        $clientId = $clientId ?? $this->config->getClientId($environment);
        $clientSecret = $clientSecret ?? $this->config->getClientSecret($environment);
        $baseUrl = $this->config->getUrl($environment);
        $url = sprintf('%s/users/%s/tokens', $baseUrl, $userId);
        $cacheKey = $this->cache->generateCacheIdUsingData([
            $environment,
            $clientId,
            $userId,
            $clientSecret,
            $baseUrl
        ]);
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            return $cached;
        }

        $curlClient->addHeader('Content-Type', 'application/json');
        $curlClient->post($url, $this->serializer->serialize([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]));
        $bearer = $this->serializer->unserialize($curlClient->getBody());
        if ($bearer['error'] === true) {
            return null;
        }
        $token = '';
        if ($bearer['data'] && $bearer['data']['token']) {
            $token = $bearer['data']['token'];
        }
        $tokenInfo = $this->validateToken($token);
        $ttl = (int)$tokenInfo['expires_in'];
        $this->cache->save($token, $cacheKey, [AccessTokenCache::CACHE_TAG], $ttl);

        return $token;
    }

    /**
     * @param mixed $token
     * @return array|bool|float|int|string|null
     * @throws LocalizedException
     */
    private function validateToken(mixed $token)
    {
        [, $payload] = explode('.', $token);
        if (!$payload) {
            throw new LocalizedException(__('Empty payload on token'));
        }
        $payload = @base64_decode($payload);
        if (!$payload) {
            throw new LocalizedException(__('Token cannot be decode'));
        }

        $payload = $this->serializer->unserialize($payload);
        return [
            'token' => $token,
            'expires_in' => $payload['exp'] - time(),
        ];
    }

    /**
     * @param string $path
     * @return string
     */
    private function getEndpointUrl(string $path): string
    {
        $baseUrl = $this->config->getUrl();
        return $baseUrl . $path;
    }

    /**
     * @inheritDoc
     */
    public function getPayData(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $payment = $order->getPayment();
        $curlClient = $this->getCurlClient();
        $curlClient->addHeader('Content-Type', 'application/json');
        $url = $this->getEndpointUrl('/payments/');

        $customerEmail = $order->getCustomerEmail();

        $dataPost = $this->serializer->serialize([
            'magento' => [
                'order_id' => $order->getId(),
                'store_id' => $this->config->getTaloPayStoreId(),
                'app_id' => $this->config->getTaloPayAppId(),
                'order_number' => $order->getRealOrderId(),
                'client_email' => $customerEmail,
            ],
            'price' => [
                'amount' => (float)$payment->getAmountOrdered(),
                'currency' => $order->getOrderCurrencyCode(),
            ],
            'payment_options' => ['transfer'],
            'user_id' => $this->config->getUserId(),
            'webhook_url' => $this->urlBuilder->getUrl('talopay/transfer/callback'),
            'redirect_url' => $this->urlBuilder->getUrl('talopay/transfer/redirect'),
            'external_id' => $order->getRealOrderId()
        ]);

        $body = '';
        try {
            $curlClient->post($url, $dataPost);
            $body = $curlClient->getBody();

            if ($curlClient->getStatus() < 200 || $curlClient->getStatus() >= 400) {
                throw new ApiException(__(
                    'Invalid status "%1" on TaloPay API service',
                    $curlClient->getStatus()
                ));
            }

            $response = $this->serializer->unserialize($body);
            if (isset($response['data'])) {
                return $response['data'];
            }
            throw new LocalizedException(__('Invalid response data'));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['body' => $body, 'url' => $url, 'request' => $dataPost]);
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPayment($paymentId): array
    {
        $curlClient = $this->getCurlClient();
        $curlClient->addHeader('Content-Type', 'application/json');
        $url = $this->getEndpointUrl(sprintf('/payments/%s', $paymentId));

        $body = '';
        try {
            $curlClient->get($url);
            $body = $curlClient->getBody();

            $paymentInfo = $this->serializer->unserialize($body);
            if (isset($paymentInfo['error']) && !!$paymentInfo['error']) {
                throw new LocalizedException(__($paymentInfo['message']));
            }

            return $paymentInfo['data'];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['body' => $body, 'paymentId' => $paymentId]);
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function testCredentials(string $environment, string $userId, string $clientId, string|null $clientSecret): bool
    {
        $bearer = $this->getBearerToken($environment, $userId, $clientId, $clientSecret);
        if ($bearer) {
            return true;
        }

        return false;
    }
}
