<?php

namespace TaloPay\Transfer\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Ramsey\Uuid\Uuid;

/**
 * Class Transfer Payment
 *
 *
 */
class Transfer extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected const CODE = 'talopay_transfer';
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Refund availability option
     *
     * @var bool
     */
    protected $_canRefund = false;

    /**
     * Partial refund availability option
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = false;

    /**
     * Payment Method Api Client
     *
     * @var \TaloPay\Transfer\Api\TaloApiClient
     */
    protected $_taloApiClient;

    protected const LOG_NAME = 'talopay_transfer';

    /**
     * Helper to access and send messages
     *
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager; //Missing PHP DocBlock for class property.

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * Transfer Payment Method constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \TaloPay\Transfer\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \TaloPay\Transfer\Api\TaloApiClient $taloApiClient
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \TaloPay\Transfer\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \TaloPay\Transfer\Api\TaloApiClient $taloApiClient,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_helperData = $helper;
        $this->_storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->_taloApiClient = $taloApiClient;
        $this->_orderRepository = $orderRepository;
        $this->_curl = $curl;
    }
    
    /**
     * Check if the payment method is available
     *
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        $isAvailable = $this->_helperData->isAvailable();
        if (!$isAvailable) {
            return false;
        }
        return parent::isAvailable($quote);
    }

    /**
     * Get the store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    /**
     * Generate webhook url for payment
     *
     * @param Order $order
     * @return array
     */
    private function getWebhookUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl() . 'talopay/webhook/payment';
    }

    /**
     * Generate createPayment payload
     *
     * @param Order $order
     * @return array
     */
    public function getPayload($order)
    {
        $grandTotal = $order->getGrandTotal();
        $storeName = $this->getStoreName();
        $this->_helperData->log(
            'Transfer::getPayload - Store Name',
            self::LOG_NAME,
            $storeName
        );
        $customer = $this->getCustomerData($order);
        $this->_helperData->log(
            'Transfer::getPayload - Customer Data',
            self::LOG_NAME,
            $customer
        );
        #print order
        $this->_helperData->log(
            'Transfer::getPayload - Order Data',
            self::LOG_NAME,
            $order->getData()
        );
        $orderId = $order->getIncrementId();
    
        return [
            'price' => [
                'amount' => $grandTotal,
                'currency' => $order->getOrderCurrencyCode(),
            ],
            'user_id' => $this->_helperData->getTaloPayUserId(),
            'payment_options' => ['transfer'],
            'external_id' => $orderId,
            'magento'=>[
                'order_id' => $orderId,
                'store_id' => $this->_helperData->getTaloPayStoreId(),
                'app_id' => $this->_helperData->getTaloPayAppId(),
                'order_number' => $orderId,
                'client_email'=> $order->getData()['customer_email'],
            ],
            'webhook_url' => $this->getWebhookUrl(),

        ];
    }

    /**
     * Create payment for order
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param number $amount
     * @return void
     */
    public function order( //Comment block is missing
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    ) {
        try {
            $this->_helperData->log(
                'Transfer::initialize - Start create charge at TaloPay',
                self::LOG_NAME
            );
            $order = $payment->getOrder();
            $order->setState(Order::STATE_NEW);
            $payload = $this->getPayload($order);
            $this->_helperData->debugJson('Payload ', self::LOG_NAME, $payload);
            $response = (array) $this->_taloApiClient->createCharge($payload);
            if (isset($response['errors'])) {
                $arrayLog = [
                    'response' => $response,
                    'message' => [$response['errors']],
                ];

                $this->messageManager->addErrorMessage(
                    __('Error creating Payment')
                );
                $this->messageManager->addErrorMessage($response['errors']);
                $this->_helperData->log(
                    'Transfer::ResponseError - Error while creating TaloPay Charge',
                    self::LOG_NAME,
                    $arrayLog
                );
                return $this;
            }

            $this->_helperData->debugJson(
                'Transfer::ResponseSuccess - Response Payload ',
                self::LOG_NAME,
                $response
            );

            $charge = $response;
            #save payment id
            
            $order->setTalopayPaymentid($charge['id']);
            $this->_helperData->log(
                'Transfer::ResponseSuccess - Payment ID ',
                self::LOG_NAME,
                $charge['id'],
                $order->getTalopayPaymentid()
            );
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $payment->setSkipOrderProcessing(true);
            $this->_helperData->debugJson(
                "Order state", 
                self::LOG_NAME,
                $order->getStatus(),
                $order->getState()
            );
            
            return $this;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error creating Transfer Payment'));
            $this->_helperData->log(
                'Transfer::Error - Error while creating charge',
                self::LOG_NAME,
                $e->getMessage()
            );
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }
}
