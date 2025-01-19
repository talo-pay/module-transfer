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
     * @var string
     */
    protected $_token;

    /**
     * @var number
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
    }

    /**
     * Send request to TaloPay API
     *
     * @param string $method
     * @param string $url
     * @param array $body
     * @param boolean $auth
     * @return array
     */
    private function sendRequest($method, $url, $body, $auth = false)
    {
        $this->_helperData->log("Starting sendRequest", [
            'method' => $method,
            'url' => $url,
            'body' => $body,
            'auth' => $auth
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
                $this->_curl->addHeader('Authorization', 'Bearer ' . $this->getTaloPayApiAccessToken());
            }
            $apiUrl = $this->_helperData->getTaloPayApiUrl().$url;
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
     * @return void
     */
    private function throwErrorException($method, $url)
    {
        throw new LocalizedException(__('An error occurred while processing your request. '.$method.' '.$url));
    }

    /**
     * Check if token is valid
     *
     * @return boolean
     */
    public function isTokenValid(): bool
    {
        return $this->_token && time() < $this->_tokenExpiration;
    }

    /**
     * Get new token
     *
     * @param string $userId
     * @param string $clientId
     * @param string $clientSecret
     * @return string
     */
    public function getNewToken($userId, $clientId, $clientSecret)
    {
        $res = $this->sendRequest('POST', '/users/'. $userId . '/tokens', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

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
        $this->_helperData->log("Starting create payment");
        $res = $this->sendRequest('POST', '/payments/', $chargePayload, false);
        return $res;
    }

    /**
     * Create store
     *
     * @param array $storePayload
     * @return array
     */
    public function createStore($storePayload)
    {
        $userId = $this->_helperData->getTaloPayUserId();
        $res = $this->sendRequest('POST', '/users/' . $userId . '/stores', $storePayload, true);
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
        $res = $this->sendRequest('GET', '/payments/' . $paymentId, [], true);
        return $res;
    }

    /**
     * Get TaloPay API Access Token
     *
     * @return string
     */
    public function getTaloPayApiAccessToken(): string
    {
        if ($this->isTokenValid()) {
            return $this->_token;
        } else {
            $userId = $this->_helperData->getTaloPayUserId();
            $clientId = $this->_helperData->getTaloPayClientId();
            $clientSecret = $this->_helperData->getTaloPayClientSecret();

            $token = $this->getNewToken($userId, $clientId, $clientSecret);
            $this->_token = $token;
            $this->_tokenExpiration = time() + 3600;
            return $this->_token;
        }
    }
}
