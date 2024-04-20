<?php
namespace MetaCloudify\OwnCloudBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyOwnCloudBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}