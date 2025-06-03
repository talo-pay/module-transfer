<?php

namespace TaloPay\Transfer\Helper\WebhookHandlers;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order;
use TaloPay\Transfer\Api\TaloApiClient;
use TaloPay\Transfer\Helper\Data;

class ChargePaid
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var TaloApiClient
     */
    protected $_taloApiClient;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected const LOG_NAME = 'charge_paid';

    /**
     * ChargePaid constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param Order $order
     * @param Data $_helperData
     * @param TaloApiClient $taloApiClient
     */
    public function __construct(//Comment block is missing
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Order $order,
        Data $_helperData,
        TaloApiClient $taloApiClient
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceSender = $invoiceSender;
        $this->order = $order;
        $this->_helperData = $_helperData;
        $this->messageManager = $messageManager;
        $this->_taloApiClient = $taloApiClient;
    }

    /**
     * Handle 'charge_paid' event.
     *
     * The charge can be related to a subscription or a single payment.
     *
     * @param string $paymentId
     * @return bool
     */
    public function chargePaid($paymentId)
    {
        $this->_helperData->log('TaloPay::chargePaid Start '.$paymentId, self::LOG_NAME);
        $taloPayment = $this->_taloApiClient->getTaloPayment($paymentId);

        $this->_helperData->log('TaloPay::chargePaid Payment ', self::LOG_NAME, $taloPayment);

        $order = $this->order->loadByIncrementId($taloPayment['magento']['order_id']);

        if (!$order) {
            $this->_helperData->log(
                'TaloPay::chargePaid Order Not Found',
                self::LOG_NAME
            );

            $this->_helperData->log(__(sprintf('Order Not Found')));

            return ['error' => 'Order Not Found', 'success' => null];
        }

        if ($taloPayment['payment_status'] == "SUCCESS" || $taloPayment['payment_status'] == 'OVERPAID') {
            $this->createInvoice($order, $paymentId);
            $order->addStatusHistoryComment(
                __(
                    'TaloPay confirmó el pago y la orden está siendo procesada'
                ),
                $order
                        ->getConfig()
                    ->getStateDefaultStatus(
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    )
            );
    
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
    
            $this->orderRepository->save($order);
        } elseif ($taloPayment['payment_status'] == "UNDERPAID") {
            $amount = $taloPayment['transaction_fields']['total_paid']['amount'];
            $order->addStatusHistoryComment(
                __(
                    'Se recibió un pago de '. $amount . 'pero es insuficiente para la confirmación de la orden '
                )
            );
    
            $this->orderRepository->save($order);
            return [
                'error' => null,
                'success' =>
                    'The payment was confirmed by Talopay and the order is being processed',
            ];
        } else {
            $this->_helperData->log(
                'TaloPay::chargePaid Payment Status different from PAID '.$taloPayment['payment_status'],
                self::LOG_NAME
            );
        }
    }
    
    /**
     * Checks if the order has a payment id
     *
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function hasPaymentId(\Magento\Sales\Model\Order $order): bool
    {
        $hasPaymentId = $order->getData('talopay_paymentid');

        if (isset($hasPaymentId)) {
            return true;
        }

        return false;
    }

    /**
     * Create invoice for order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentId
     * @return bool
     */
    public function createInvoice(\Magento\Sales\Model\Order $order, $paymentId)
    {

        if (!$order->canInvoice()) {
            $this->_helperData->log(
                __(
                    sprintf(
                        'Impossible to generate invoice for order %s.',
                        $order->getId()
                    )
                )
            );
            return [
                'error' => sprintf(
                    'Impossible to generate invoice for order %s.',
                    $order->getId()
                ),
                'success' => null,
            ];
        }

        $this->_helperData->log(
            "Generating invoice for the order {$order->getId()}.",
            self::LOG_NAME
        );
        $this->_helperData->log(
            __(sprintf('Generating invoice for the order %s.', $order->getId()))
        );

        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();
        $invoice->setSendEmail(true);
        $invoice->setTransactionId($paymentId);

        $this->invoiceRepository->save($invoice);

        try {
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->_helperData->log(
                'We can\'t send the invoice email right now.',
                self::LOG_NAME
            );
            $this->messageManager->addError(
                __('We can\'t send the invoice email right now.')
            );
        }

        $this->_helperData->log('Invoice created with success', self::LOG_NAME);
    }
}
