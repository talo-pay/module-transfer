<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Processor;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
use TaloPay\Transfer\Api\NotificationSenderInterface;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

class PaymentProcessor implements PaymentProcessorInterface
{
    /**
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param ConfigInterface $config
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly ConfigInterface $config,
        private readonly TransactionFactory $transactionFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        OrderInterface $order,
        string $paymentId,
        array $taloPayment,
        ?PaymentProcessorResponseInterface $previousResult = null
    ): ?PaymentProcessorResponseInterface {
        /** @var PaymentProcessorResponseInterface $previousResult */

        $payment = $order->getPayment();
        $paymentAdditionalInfo = $payment->getAdditionalInformation();
        if (!isset($paymentAdditionalInfo['talopay_transfer']) ||
            !isset($paymentAdditionalInfo['talopay_transfer']['id']) ||
            $paymentAdditionalInfo['talopay_transfer']['id'] !== $paymentId
        ) {
            throw new LocalizedException(__('Invalid payment information'));
        }

        $invoiceMessages = [];
        $notifyAll = $this->config->getNotificationEmailStatus() === NotificationSenderInterface::STATUS_ON_TRANSACTION;
        $notifyTotal = $this->config->getNotificationEmailStatus() === NotificationSenderInterface::STATUS_ON_TOTAL;
        $invoices = [];
        foreach ($taloPayment['transactions'] as $transaction) {
            $amount = (float)$transaction['amount'];
            $trxid = $transaction['transaction_id'];
            $invoiceList = $order->getInvoiceCollection();
            foreach ($invoiceList->getItems() as $invoiceItem) {
                if ($invoiceItem->getTransactionId() === $trxid) {
                    continue 2;
                }
            }

            /** @var InvoiceInterface $invoice */
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setTransactionId($trxid);

            // region Este bloque esta en duda de la forma
            $invoice->setSubtotal($amount);
            $invoice->setBaseSubtotal($amount);
            $invoice->setSubtotalInclTax($amount);
            $invoice->setBaseSubtotalInclTax($amount);
            $invoice->setGrandTotal($amount);
            $invoice->setBaseGrandTotal($amount);
            $invoice->register();
            $invoice->setState(Invoice::STATE_PAID);
            $order->getPayment()->pay($invoice);
            // endregion

            // Esto seguro que hay que hacerlo
            $totalPaid = $invoice->getGrandTotal();
            $baseTotalPaid = $invoice->getBaseGrandTotal();
            if (count($invoiceList->getItems()) > 1) {
                $totalPaid += $order->getTotalPaid();
                $baseTotalPaid += $order->getBaseTotalPaid();
            }
            $order->setTotalPaid($totalPaid);
            $order->setBaseTotalPaid($baseTotalPaid);

            $transaction = $this->transactionFactory->create();
            $transaction->addObject($invoice);
            $transaction->addObject($invoice->getOrder());
            $transaction->save();

            $invoice->getExtensionAttributes()->setDueAmount((float)$order->getTotalDue())
                ->setBaseDueAmount((float)$order->getBaseTotalDue());

            $this->invoiceSender->send($invoice);
            $invoiceMessages[] = __(
                'The invoice #%1 was been generated with transaction id %2',
                $invoice->getIncrementId(),
                $trxid
            );

            if ($notifyAll ||
                (float)$order->getTotalDue() === 0.0 && $notifyTotal) {
                $invoices[$invoice->getId()] = $invoice;
            }
        }

        $previousResult->setInvoicesToNotify($invoices);

        if ($invoiceMessages) {
            $previousResult->addComments($invoiceMessages);
        }

        return $previousResult;
    }
}
