<?php

namespace UEMC\Core\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class UemcLogger implements LoggerInterface
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('uemc');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../uemc.log', Logger::DEBUG));
    }

    public function emergency($message, array $context = array())
    {
        $this->logger->emergency($message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->logger->alert($message, $context);
    }

    public function critical($message, array $context = array())
    {  // Ensured consistent method name
        $this->logger->critical($message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->logger->error($message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->logger->warning($message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->logger->notice($message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->logger->info($message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $message, $context);
    }
}