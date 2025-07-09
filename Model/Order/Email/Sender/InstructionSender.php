<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model\Order\Email\Sender;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Payment\Helper\Data;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\Template;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class InstructionSender
{
    /**
     * @param ConfigInterface $config
     * @param ScopeConfigInterface $scopeConfig
     * @param Template $templateContainer
     * @param LoggerInterface $logger
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param Data $paymentHelper
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Template $templateContainer,
        private readonly LoggerInterface $logger,
        private readonly StateInterface $inlineTranslation,
        private readonly TransportBuilder $transportBuilder,
        private readonly \Magento\Payment\Helper\Data $paymentHelper,
    ) {
    }

    /**
     * @param Order $order
     * @param $force
     * @return bool
     */
    public function send(Order $order, $force = false)
    {
        if (!$this->config->mustSendInstructions() &&
            $force === false) {
            return false;
        }

        try {
            $this->inlineTranslation->suspend();
            if ($order->getCustomerIsGuest()) {
                $templateId = $this->config->getInstructionsGuestTemplateId();
                $customerName = $order->getBillingAddress()->getName();
            } else {
                $templateId = $this->config->getInstructionsTemplateId();
                $customerName = $order->getCustomerName();
            }

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $order->getStoreId(),
                ])
                ->setTemplateVars([
                    'order' => $order,
                    'order_id' => $order->getId(),
                    'billing' => $order->getBillingAddress(),
                    'payment_html' => $this->getPaymentHtml($order),
                    'store' => $order->getStore(),
                    'order_data' => [
                        'customer_name' => $order->getCustomerName(),
                        'is_not_virtual' => $order->getIsNotVirtual(),
                        'email_customer_note' => $order->getEmailCustomerNote(),
                        'frontend_status_label' => $order->getFrontendStatusLabel()
                    ]
                ])
                ->setFromByScope(
                    $this->scopeConfig->getValue('sales_email/order/identity'),
                    $order->getStoreId()
                )
                ->addTo($order->getCustomerEmail(), $order->getCustomerName())
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws Exception
     */
    private function getPaymentHtml(OrderInterface $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $order->getStoreId()
        );
    }

}
