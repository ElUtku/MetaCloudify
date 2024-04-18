<?php
namespace MetaCloudify\_home;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudify_homeBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}