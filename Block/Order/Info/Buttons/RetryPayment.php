<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Block\Order\Info\Buttons;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Order\Info\Buttons;
use TaloPay\Transfer\Api\ConfigInterface;

class RetryPayment extends Buttons
{
    /**
     * @var string
     */
    protected $_template = 'TaloPay_Transfer::order/info/buttons/transfer/retry.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param HttpContext $httpContext
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        HttpContext $httpContext,
        private readonly ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $registry, $httpContext, $data);
    }

    /**
     * @return bool
     */
    public function canRetry()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        if ($payment->getMethod() !== ConfigInterface::PAYMENT_CODE) {
            return false;
        }

        if ($order->getTotalDue() <= 0) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getRetryUrl()
    {
        return $this->getUrl('talopay/transfer/retry', [
            'order_id' => $this->getOrder()->getId(),
            'key' => $this->config->getOrderKey($this->getOrder())
        ]);
    }
}
