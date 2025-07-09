<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Plugin\Sales\Block\Adminhtml\Order\View;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class AddSendInstructionButtonPlugin
{
    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        readonly private AuthorizationInterface $authorization,
    ) {
    }

    /**
     * Add a button to send payment instruction
     *
     * @param OrderView $view
     * @return void
     */
    public function beforeSetLayout(
        OrderView $view
    ) {
        if (!$this->authorization->isAllowed('TaloPay_Transfer::instruction_email')) {
            return;
        }
        $message = __('Are you sure you want to send the payment instructions email to customer?');
        $url = $view->getUrl('talopay/transfer/sendinstructions', ['id' => $view->getOrder()->getId()]);
        $view->addButton(
            'talopay_transfer_sendinstructions',
            [
                'label' => __('Send Payment Instructions'),
                'onclick' => "confirmSetLocation('{$message}', '{$url}')"
            ],
            0,
            10
        );
    }
}
