<?php

namespace UEMC\Core\Service;

use Exception;

class CloudException extends Exception
{
    // Código de error HTTP por defecto
    /**
     * @var int
     */
    protected int $statusCode = 500;
    private UemcLogger $logger;

    /**
     * @param $message
     * @param $code
     * @param Exception|null $previous
     */
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->logger=new UemcLogger();
        $this->logger->ERROR('Code: '.$code. ' - Message: '.$message);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return void
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }
}