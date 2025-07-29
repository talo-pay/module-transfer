<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use TaloPay\Transfer\Api\ConfigInterface;
use TaloPay\Transfer\Api\NotificationSenderInterface;

class NotificationSender implements NotificationSenderInterface
{
    /**
     * @param ConfigInterface $config
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly StateInterface $inlineTranslation,
        private readonly TransportBuilder $transportBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function notifyInvoices(OrderInterface $order, array $invoices): NotificationSenderInterface
    {
        if ($this->config->getNotificationEmailStatus() === NotificationSenderInterface::STATUS_DISABLED) {
            return $this;
        }

        if (count($invoices) === 0) {
            return $this;
        }

        try {
            $this->inlineTranslation->suspend();
            $templateId = $this->config->getNotificationEmailTemplate();
            if ($order->getCustomerIsGuest()) {
                $customerName = $order->getBillingAddress()->getName();
            } else {
                $customerName = $order->getCustomerName();
            }
            foreach ($invoices as $invoice) {
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier($templateId)
                    ->setTemplateOptions([
                        'area' => Area::AREA_FRONTEND,
                        'store' => $order->getStoreId(),
                    ])
                    ->setTemplateVars([
                        'order' => $order,
                        'paid_amount' => $order->formatPriceTxt($invoice->getGrandTotal()),
                        'due_amount' => $order->formatPriceTxt($invoice->getExtensionAttributes()->getDueAmount()),
                        'has_due' => $invoice->getExtensionAttributes()->getDueAmount() > 0,
                        'order_id' => $order->getId(),
                        'store' => $order->getStore(),
                        'order_data' => [
                            'customer_name' => $customerName,
                            'is_not_virtual' => $order->getIsNotVirtual(),
                            'email_customer_note' => $order->getEmailCustomerNote(),
                            'frontend_status_label' => $order->getFrontendStatusLabel()
                        ]
                    ])
                    ->setFromByScope(
                        $this->scopeConfig->getValue('sales_email/order/identity'),
                        $order->getStoreId()
                    )
                    ->addTo($order->getCustomerEmail(), $customerName)
                    ->getTransport();
                $transport->sendMessage();
            }
            $this->inlineTranslation->resume();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this;
    }
}
