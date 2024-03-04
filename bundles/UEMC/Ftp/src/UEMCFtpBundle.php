<?php
namespace UEMC\Ftp;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCFtpBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}