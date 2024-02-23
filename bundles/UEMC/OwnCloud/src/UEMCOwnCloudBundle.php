<?php
namespace UEMC\OwnCloud;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCOwnCloudBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}