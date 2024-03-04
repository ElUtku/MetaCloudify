<?php
namespace UEMC\OneDrive;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCOneDriveBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}