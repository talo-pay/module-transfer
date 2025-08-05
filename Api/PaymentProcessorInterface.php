<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Api;

use Magento\Sales\Api\Data\OrderInterface;

interface PaymentProcessorInterface
{
    /**
     * Execute the payment processor
     *
     * @param OrderInterface $order
     * @param string $paymentId
     * @param array $taloPayment
     * @param \TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface|null $previousResult
     * @return \TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface|null
     */
    public function execute(
        \Magento\Sales\Api\Data\OrderInterface $order,
        string $paymentId,
        array $taloPayment,
        ?\TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface $previousResult = null
    ): ?\TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
}
