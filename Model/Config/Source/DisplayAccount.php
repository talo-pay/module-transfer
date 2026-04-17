<?php
/**
 * Talopay_Transfer
 *
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class DisplayAccount implements OptionSourceInterface
{
    /**
     * Return option arrau
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($item) {
            return [$item['value'] => $item['label']];
        }, $this->toOptionArray());
    }

    /**
     * Return option array
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ConfigInterface::DISPLAY_ACCOUNT_BOTH,
                'label' => __('Both'),
            ],
            [
                'value' => ConfigInterface::DISPLAY_ACCOUNT_ALIAS,
                'label' => __('Alias')
            ],
            [
                'value' => ConfigInterface::DISPLAY_ACCOUNT_CVU,
                'label' => __('CVU')
            ]
        ];
    }
}
