<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Block\System\Config\Button;

use Magento\AdvancedSearch\Block\Adminhtml\System\Config\TestConnection;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class TestCredential extends TestConnection
{
    /**
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData([
            'button_label' => __($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'ajax_url' => $this->_urlBuilder->getUrl('talopay/transfer/testcredentials'),
            'field_mapping' => $this->_escaper->escapeJs($this->serializer->serialize($this->_getFieldMapping())),
        ]);

        return $this->_toHtml();
    }

    /**
     * @return string[]
     */
    protected function _getFieldMapping(): array
    {
        return [
            'environment' => 'payment_other_talopay_transfer_environment',
            'sandbox_user_id' => 'payment_other_talopay_transfer_sandbox_credentials_user_id',
            'sandbox_client_id' => 'payment_other_talopay_transfer_sandbox_credentials_client_id',
            'sandbox_client_secret' => 'payment_other_talopay_transfer_sandbox_credentials_client_secret',
            'production_user_id' => 'payment_other_talopay_transfer_production_credentials_user_id',
            'production_client_id' => 'payment_other_talopay_transfer_production_credentials_client_id',
            'production_client_secret' => 'payment_other_talopay_transfer_production_credentials_client_secret',
        ];
    }
}
