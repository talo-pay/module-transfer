<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;
use TaloPay\Transfer\Api\ConfigInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param UrlInterface $urlBuilder
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                'talopay_transfer' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('talopay/transfer/start'),
                    'instructions' => $this->config->getInstructions()
                ]
            ]
        ];
    }
}
