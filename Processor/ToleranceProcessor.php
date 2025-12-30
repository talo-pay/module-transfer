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
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Api\Data\PaymentProcessorResponseInterface;
use TaloPay\Transfer\Api\NotificationSenderInterface;
use TaloPay\Transfer\Api\PaymentProcessorInterface;

class ToleranceProcessor implements PaymentProcessorInterface
{
    /**
     * @param ConfigInterface $config
     * @param InvoiceSender $invoiceSender
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly InvoiceSender $invoiceSender,
        private readonly InvoiceService $invoiceService,
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
        $paymentSuccess = $taloPayment['status'] === 'SUCCESS';
        if (!$paymentSuccess) {
            return $previousResult;
        }

        if ($order->getTotalDue() <= 0) {
            return $previousResult;
        }

        $notifyAll = $this->config->getNotificationEmailStatus() === NotificationSenderInterface::STATUS_ON_TRANSACTION;
        $notifyTotal = $this->config->getNotificationEmailStatus() === NotificationSenderInterface::STATUS_ON_TOTAL;

        // Si hay deuda en el pedido y el estado actual del pago es success se prepara el invoice para
        // el total de lo adeudado.
        $diffAmount = $order->getTotalDue();
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setSubtotal($diffAmount);
        $invoice->setBaseSubtotal($diffAmount);
        $invoice->setSubtotalInclTax($diffAmount);
        $invoice->setBaseSubtotalInclTax($diffAmount);
        $invoice->setGrandTotal($diffAmount);
        $invoice->setBaseGrandTotal($diffAmount);
        $invoice->register();
        $invoice->setState(Invoice::STATE_PAID);
        $order->getPayment()->pay($invoice);

        $invoice->getExtensionAttributes()
            ->setDueAmount(0.0)
            ->setBaseDueAmount(0.0);

        $order->setTotalPaid($invoice->getGrandTotal() + $order->getTotalPaid());
        $order->setBaseTotalPaid($invoice->getBaseGrandTotal() + $order->getBaseTotalPaid());

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice);
        $transaction->addObject($invoice->getOrder());
        $transaction->save();

        $invoice->getExtensionAttributes()->setDueAmount((float)$order->getTotalDue())
            ->setBaseDueAmount((float)$order->getBaseTotalDue());

        $this->invoiceSender->send($invoice);

        $previousResult->addComment(__(
            'The invoice #%1 was generated due to Talo\'s tolerance policy.',
            $invoice->getIncrementId()
        ));

        if ($notifyAll ||
            (float)$order->getTotalDue() === 0.0 && $notifyTotal) {
            $invoices = $previousResult->getInvoicesToNotify();
            $invoices[$invoice->getId()] = $invoice;
            $previousResult->setInvoicesToNotify($invoices);
        }

        return $previousResult;
    }
}
