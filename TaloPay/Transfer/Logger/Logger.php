<?php

namespace TaloPay\Transfer\Logger;

class Logger extends \Monolog\Logger
{
    /**
     * Set logger name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
