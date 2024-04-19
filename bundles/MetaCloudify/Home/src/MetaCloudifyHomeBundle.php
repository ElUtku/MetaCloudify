<?php
namespace MetaCloudify\Home;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyHomeBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}