<?php

namespace TaloPay\Transfer\Block\Order\Payment;

class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * Checkout Session reference
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Order Factory reference
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_orderFactory;

    /**
     * TaloPay Helper
     *
     * @var TaloPay\Transfer\Helper\Data;
     */
    protected $_helperData;

    /**
     * Html Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    public $escaper;

    protected const LOG_NAME = 'transfer_sales_order_block';

    /**
     * Info constructor.
     *
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order $orderFactory
     * @param \TaloPay\Transfer\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $orderFactory,
        \TaloPay\Transfer\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helper;
        $this->escaper = $escaper;
    }

    /**
     * Get Payment Method
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $order = $this->_orderFactory->load($order_id);
        $payment = $order->getPayment();

        $this->_helperData->log(
            'Transfer::Block - Sales Order getPaymentMethod',
            self::LOG_NAME,
            $payment->getMethod()
        );
        return $payment->getMethod();
    }

    /**
     * Get Payment ID
     *
     * @return string
     */
    public function getPaymentId()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $order = $this->_orderFactory->load($order_id);
        $paymentId = $order->getTalopayPaymentid();
        $this->_helperData->log(
            'Transfer::Block - Sales Order getPaymentId',
            self::LOG_NAME,
            $paymentId
        );
        if (isset($paymentId)) {
            return $paymentId;
        }

        return '';
    }

    /**
     * Get Payment Info
     *
     * @return array
     */
    public function getPaymentInfo()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $order = $this->_orderFactory->load($order_id);

        return [
            'alias' => $order->getTaloPayAlias(),
            'cvu' => $order->getTaloPayCvu(),
            'amount' => $order->getTaloPayAmount()
        ];
    }
    
    /**
     * Get TaloPay Plugin URL
     *
     * @return string
     */
    public function getPluginSrc(): string
    {
        return $this->_helperData->getTaloPayPluginUrlScript();
    }
}
