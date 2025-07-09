<?php
/**
 * Talopay_Transfer
 * 
 * @author TaloPay https://talo.com.ar/
 * @license OSL-3.0 https://opensource.org/license/osl-3.0.php
 */
declare(strict_types = 1);

namespace TaloPay\Transfer\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class AccessToken extends TagScope
{
    public const TYPE_IDENTIFIER = 'talopay_transfer_token';
    public const CACHE_TAG = 'TALOPAY_TRANSFER_TOKEN_CACHE_TAG';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool,
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }

    /**
     * @param array $data
     * @return string
     */
    public function generateCacheIdUsingData($data)
    {
        return hash('md5', implode('|', $data));
    }
}
