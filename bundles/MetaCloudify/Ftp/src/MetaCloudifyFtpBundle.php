<?php
namespace MetaCloudify\Ftp;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyFtpBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}