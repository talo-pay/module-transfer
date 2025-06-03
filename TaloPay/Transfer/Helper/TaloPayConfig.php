<?php

namespace TaloPay\Transfer\Helper;

use Magento\TestFramework\Utility\ChildrenClassesSearch\C;

class TaloPayConfig
{
    protected const LOG_NAME = 'talopay_transfer';
    
    /**
     * Get the TaloPay API URL
     *
     * @return string
     */
    public function getTaloPayApiUrl($env = 'production')
    {
        if ($env === 'production') {
            return 'https://api.talo.com.ar';
        } else {
            return 'https://sandbox-api.talo.com.ar';
        }
    }

    /**
     * Get the TaloPay Platform URL
     *
     * @return string
     */
    public function getTaloPayPlatformUrl($env = 'production')
    {
        if ($env === 'production') {
            return 'https://app.talo.com.ar/';
        } else {
            return 'https://sandbox.talo.com.ar/';
        }
    }

    /**
     * Get the TaloPay Plugin URL
     *
     * @param string $env
     * @return string
     */
    public function getTaloPayPluginUrlScript($env ='production'): string
    {
        if ($env === 'production') {
            return 'https://talo-public-res.s3.us-east-1.amazonaws.com/magento/prod/talo-cvus-front.js';
        } else {
            return 'https://talo-public-res.s3.us-east-1.amazonaws.com/magento/sandbox/talo-cvus-front.js';
        }
    }
}
