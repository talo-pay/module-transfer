<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Api;

use Exception;

interface ApiClientInterface
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_UNDERPAID = 'underpaid';
    public const STATUS_OVERPAID = 'overpaid';

    /**
     * @param array $array
     * @return mixed
     */
    public function createStore(array $array);

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return mixed
     * @throws Exception
     */
    public function getPayData(\Magento\Sales\Api\Data\OrderInterface $order);

    /**
     * @param $paymentId
     * @return array
     */
    public function getPayment($paymentId): array;

    /**
     * @param string $environment
     * @param string $userId
     * @param string $clientId
     * @param string|null $clientSecret
     * @return bool
     */
    public function testCredentials(
        string $environment,
        string $userId,
        string $clientId,
        ?string $clientSecret
    ): bool;
}
