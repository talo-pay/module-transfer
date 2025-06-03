<?php
namespace TaloPay\Transfer\Api;

use Magento\Framework\Exception\LocalizedException;
use TaloPay\Transfer\Helper\Data;
use Magento\Framework\HTTP\Client\Curl;

class TaloApiClient
{
    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var array
     */
    protected $_token;

    /**
     * @var array
     */
    protected $_tokenExpiration;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @param Data $helperData
     */
    public function __construct(
        Data $helperData,
        Curl $curl
    ) {
        $this->_curl = $curl;
        $this->_helperData = $helperData;
        $this->_token = [];
        $this->_tokenExpiration = [];
    }

    /**
     * Send request to TaloPay API
     *
     * @param string $method
     * @param string $url
     * @param array $body
     * @param boolean $auth
     * @param string $env
     * @return array
     */
    private function sendRequest($method, $url, $body, $auth = false, $env = 'production')
    {
        $this->_helperData->log("Starting sendRequest", [
            'method' => $method,
            'url' => $url,
            'body' => $body,
            'auth' => $auth,
            'env' => $env
        ]);
        try {
            $this->_curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->_curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->_curl->setOption(CURLOPT_ENCODING, '');
            $this->_curl->addHeader('Content-Type', 'application/json');
            $this->_curl->addHeader('Accept', 'application/json');
            if ($auth === true) {
                $this->_curl->addHeader('Authorization', 'Bearer ' . $this->getTaloPayApiAccessToken($env));
            }
            $apiUrl = $this->_helperData->getTaloPayApiUrl($env).$url;
            if ($method === 'POST') {
                $this->_curl->post($apiUrl, json_encode($body));
            } elseif ($method === 'GET') {
                $this->_curl->get($apiUrl);
            } else {
                $this->throwMethodNotAllowedException();
            }
            $res = $this->_curl->getBody();
            $statusCode = $this->_curl->getStatus();

            $responseBody = json_decode($res, true);

            if ($statusCode !== 200) {
                $this->_helperData->log(
                    'TaloApiClient::sendRequest - Error',
                    'talopay_transfer',
                    $responseBody
                );
                $this->throwErrorException($method, $url);
            }
            return $responseBody['data'];
        } catch (\Exception $e) {
            $this->_helperData->log(
                'TaloApiClient::sendRequest - Exception',
                'talopay_transfer',
                $e->getMessage()
            );
            throw new LocalizedException(__('An error occurred while processing your request. '.$url));
        }
    }

    /**
     * Throw Method Not Allowed Exception
     *
     * @return void
     */
    private function throwMethodNotAllowedException()
    {
        throw new LocalizedException(__('Method not allowed'));
    }

    /**
     * Throw Error Exception
     *
     * @param string $method
     * @param string $url
     * @param string $env
     * @return void
     */
    private function throwErrorException($method, $url, $env = 'production')
    {
        throw new LocalizedException(__('An error occurred while processing your request. '.$method.' '.$url.' '.$env));
    }

    /**
     * Check if token is valid
     *
     * @param string $env
     * @return boolean
     */
    public function isTokenValid($env = 'production'): bool
    {
        if (!isset($this->_token[$env])) {
            return false;
        }
        return $this->_token[$env] && time() < $this->_tokenExpiration[$env];
    }

    /**
     * Get new token
     *
     * @param string $userId
     * @param string $clientId
     * @param string $clientSecret
     * @param string $env
     * @return string
     */
    public function getNewToken($userId, $clientId, $clientSecret, $env = 'production')
    {
        $res = $this->sendRequest('POST', '/users/'. $userId . '/tokens', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ], false, $env);

        return $res['token'];
    }

    /**
     * Create charge
     *
     * @param array $chargePayload
     * @return array
     */
    public function createCharge($chargePayload)
    {
        $activeEnv = $this->_helperData->getConfig('payment/talopay_transfer/talopay_sandbox_mode') === '1' ? 'sandbox' : 'production';
        $this->_helperData->log("Starting create payment", [
            'env' => $activeEnv
        ]);
        $res = $this->sendRequest('POST', '/payments/', $chargePayload, false, $activeEnv);
        return $res;
    }

    /**
     * Create store
     *
     * @param array $storePayload
     * @param string $env
     * @return array
     */
    public function createStore($storePayload, $env = 'production')
    {
      
        $userId = $this->_helperData->getTaloPayUserId($env);
        $res = $this->sendRequest('POST', '/users/' . $userId . '/stores', $storePayload, true, $env);
      
        return $res;
    }

    /**
     * Get TaloPay Payment
     *
     * @param string $paymentId
     * @return array
     */
    public function getTaloPayment($paymentId)
    {
        $activeEnv = $this->_helperData->getConfig('payment/talopay_transfer/talopay_sandbox_mode') === '1' ? 'sandbox' : 'production';
        $res = $this->sendRequest('GET', '/payments/' . $paymentId, [], true, $activeEnv);
        return $res;
    }

    /**
     * Get TaloPay API Access Token
     *
     * @param string $env
     * @return string
     */
    public function getTaloPayApiAccessToken($env = 'production'): string
    {
        if ($this->isTokenValid($env)) {
            return $this->_token[$env];
        } else {
            $userId = $this->_helperData->getTaloPayUserId($env);
            $clientId = $this->_helperData->getTaloPayClientId($env);
            $clientSecret = $this->_helperData->getTaloPayClientSecret($env);

            $token = $this->getNewToken($userId, $clientId, $clientSecret, $env);

            $this->_token[$env] = $token;
            $this->_tokenExpiration[$env] = time() + 3600;
            return $this->_token[$env];
        }
    }
}
