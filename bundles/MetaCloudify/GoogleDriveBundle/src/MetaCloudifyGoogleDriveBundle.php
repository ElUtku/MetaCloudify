<?php
namespace MetaCloudify\GoogleDriveBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyGoogleDriveBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}