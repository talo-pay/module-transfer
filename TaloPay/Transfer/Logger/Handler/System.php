<?php

namespace TaloPay\Transfer\Logger\Handler;

use Monolog\Logger;

/**
 * TaloPay logger handler
 */
class System extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * Log file name
     *
     * @var string
     */
    protected $fileName = '/var/log/talopay.log';
}
