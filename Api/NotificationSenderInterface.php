<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Api;

interface NotificationSenderInterface
{
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ON_TRANSACTION = 'transaction';
    public const STATUS_ON_TOTAL = 'total';

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Model\Order\Invoice[] $invoices
     * @return self
     */
    public function notifyInvoices(
        \Magento\Sales\Api\Data\OrderInterface $order,
        array $invoices
    ): NotificationSenderInterface;
}
