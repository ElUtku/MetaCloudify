<?php
namespace MetaCloudify\HomeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyHomeBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}