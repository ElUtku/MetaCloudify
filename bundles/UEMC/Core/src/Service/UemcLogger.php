<?php

namespace UEMC\Core\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class UemcLogger
{
    /**
     * @var LoggerInterface
     */
    public LoggerInterface $loggerUEMC;

    /**
     *
     */
    public function __construct()
    {
        $this->loggerUEMC = new Logger("uemc");
        $this->loggerUEMC->pushHandler(new StreamHandler(__DIR__."../../../uemc.log", Logger::DEBUG));
    }

}