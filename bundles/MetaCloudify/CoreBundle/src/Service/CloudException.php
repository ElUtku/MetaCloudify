<?php

namespace MetaCloudify\CoreBundle\Service;

use Exception;

class CloudException extends Exception
{
    private MetaCloudifyLogger $logger;

    /**
     * @param $message
     * @param $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logger = new MetaCloudifyLogger();
        $this->logger->ERROR('Code: ' . $code . ' - Message: ' . $message . ' - Trace: ' . $this->getTraceAsString());
    }
}