<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Block\Payment\View;

use Magento\Checkout\Model\Session;
use Magento\Framework\CurrencyInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class Instructions extends Template
{
    protected $_template = 'order/view/instructions.phtml';

    /**
     * @param Context $context
     * @param CurrencyInterface $currency
     * @param Session $checkoutSession
     * @param ArrayManager $arrayManager
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        readonly private CurrencyInterface $currency,
        readonly private Session $checkoutSession,
        readonly private ArrayManager $arrayManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array|string
     */
    public function getTaloIssuerName()
    {
        return $this->getPaymentAdditionalInformation('user_info/full_name', '');
    }

    /**
     * @param string $path
     * @param string|null $defaultValue
     * @return string|null|array
     */
    private function getPaymentAdditionalInformation(string $path, string $defaultValue = null)
    {
        $taloData = $this->getPayment()->getAdditionalInformation(ConfigInterface::ORDER_ADDITIONAL_KEY) ?? [];
        return $this->arrayManager->get($path, $taloData, $defaultValue) ?? $defaultValue;
    }

    /**
     * @return false|float|\Magento\Framework\DataObject|OrderPaymentInterface|mixed|null
     */
    public function getPayment()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        return $order->getPayment();
    }

    /**
     * @return array|string
     */
    public function getTaloIssuerTaxVatId()
    {
        return $this->getPaymentAdditionalInformation('user_info/cuit', '');
    }

    /**
     * @return array|string
     * @throws LocalizedException
     */
    public function getTaloQuotes()
    {
        return $this->getPaymentAdditionalInformation('quotes', '') ?: [];
    }

    /**
     * Retrieves the expiration date in 'd/m' format.
     *
     * @return string The formatted expiration date or an empty string if it cannot be determined.
     */
    public function getExpirationDate()
    {
        $dateString = $this->getPaymentAdditionalInformation('expiration_timestamp', '');
        if (!empty($dateString)) {
            try {
                if (is_numeric($dateString)) {
                    $date = new \DateTime();
                    $date->setTimestamp((int)$dateString);
                } else {
                    $date = new \DateTime($dateString);
                }
                return $date->format('d/m');
            } catch (\Exception $e) {
                return '';
            }
        }
        return '';
    }

    /**
     * Return true if talo pay is the payment method
     *
     * @return bool
     */
    public function isTaloPayTransferMethod()
    {
        return $this->getPayment()->getMethod() === ConfigInterface::PAYMENT_CODE;
    }

    /**
     * @param $amount
     * @param $currency
     * @return string
     * @throws \Magento\Framework\Currency\Exception\CurrencyException
     */
    public function toCurrency($amount, $currency = null)
    {
        return $this->currency->toCurrency($amount, [
            'currency' => $currency,
        ]);
    }
}
