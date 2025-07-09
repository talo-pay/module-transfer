<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Block;

use Magento\Payment\Block\Info as MagentoInfo;
use TaloPay\Transfer\Api\ConfigInterface;

class Info extends MagentoInfo
{
    protected $_template = 'TaloPay_Transfer::info/instructions.phtml';

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSpecificInformation()
    {
        $orderPayment = $this->getInfo();
        $additionalInformation = $orderPayment->getAdditionalInformation(ConfigInterface::ORDER_ADDITIONAL_KEY);
        if (!$additionalInformation) {
            return parent::getSpecificInformation();
        }

        return [
            ...parent::getSpecificInformation(),
            ...$additionalInformation
        ];
    }
}
