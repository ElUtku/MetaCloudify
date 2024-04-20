<?php
namespace MetaCloudify\OneDriveBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyOneDriveBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}