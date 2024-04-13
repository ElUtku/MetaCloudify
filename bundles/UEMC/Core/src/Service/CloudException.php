<?php

namespace UEMC\Core\Service;

use Exception;

class CloudException extends Exception
{
    private UemcLogger $logger;

    /**
     * @param $message
     * @param $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logger = new UemcLogger();
        $this->logger->ERROR('Code: ' . $code . ' - Message: ' . $message . ' - Trace: ' . $this->getTraceAsString());
    }
}