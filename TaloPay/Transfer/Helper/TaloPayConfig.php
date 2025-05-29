<?php

namespace TaloPay\Transfer\Helper;

use Magento\TestFramework\Utility\ChildrenClassesSearch\C;

class TaloPayConfig
{
    protected const LOG_NAME = 'talopay_transfer';
    protected const TALOPAY_ENV = 'prod';

    /**
     * Get the TaloPay API URL
     *
     * @return string
     */
    public function getTaloPayApiUrl()
    {
        return 'https://api.talo.com.ar';
    }

    /**
     * Get the TaloPay Platform URL
     *
     * @return void
     */
    public function getTaloPayPlatformUrl()
    {
        return 'https://app.talo.com.ar/';
    }

    /**
     * Get the TaloPay Plugin URL
     *
     * @return string
     */
    public function getTaloPayPluginUrlScript(): string
    {
        return 'https://talo-public-res.s3.us-east-1.amazonaws.com/magento/prod/talo-cvus-front.js';
    }
}
