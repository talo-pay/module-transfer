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

class Environment implements OptionSourceInterface
{
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_PRODUCTION = 'production';

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
                'value' => static::ENV_SANDBOX,
                'label' => __('Sandbox')
            ],
            [
                'value' => static::ENV_PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
