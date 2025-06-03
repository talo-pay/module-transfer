<?php

namespace TaloPay\Transfer\Block\Checkout;

class Success extends \Magento\Sales\Block\Order\Totals
{
    /**
     * Checkout Session reference
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Customer Session reference
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Order Factory reference
     *
     * @var \Magento\Sales\Model\OrderFactory
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

    protected const LOG_NAME = 'transfer_checkout_success_block';

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \TaloPay\Transfer\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Escaper $escaper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \TaloPay\Transfer\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_helperData = $helper;
        $this->escaper = $escaper;
    }

    /**
     * Get Payment ID
     *
     * @return string
     */
    public function getPaymentId()
    {
        $order = $this->getOrder();
        $paymentId = $order->getTalopayPaymentid();
        $this->_helperData->log(
            'Transfer::Block - Checkout Success getPaymentId',
            self::LOG_NAME,
            $paymentId
        );
        return $paymentId;
    }

    /**
     * Get Env
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->_helperData->getEnv(null);
    }

    /**
     * Get Order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $order = $this->_order = $this->_orderFactory
            ->create()
            ->loadByIncrementId($this->checkoutSession->getLastRealOrderId());

        $cvu = $order->getTaloPayCvu();
        $alias = $order->getTaloPayAlias();
        $amount = $order->getTaloPayAmount();

        $this->_helperData->log(
            'Transfer::Block - Checkout Success $cvu',
            self::LOG_NAME,
            $cvu
        );
        $this->_helperData->log(
            'Transfer::Block - Checkout Success $alias',
            self::LOG_NAME,
            $alias
        );
        $this->_helperData->log(
            'Transfer::Block - Checkout Success $amount',
            self::LOG_NAME,
            $amount
        );

        return $order;
    }

    /**
     * Get Plugin SRC URL
     *
     * @return string
     */
    public function getPluginSrc(): string
    {
        return $this->_helperData->getTaloPayPluginUrlScript();
    }

    /**
     * Get Customer ID
     *
     * @return string
     */
    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
}
