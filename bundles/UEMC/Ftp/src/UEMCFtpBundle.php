<?php
namespace UEMC\Ftp;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCFtpBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}