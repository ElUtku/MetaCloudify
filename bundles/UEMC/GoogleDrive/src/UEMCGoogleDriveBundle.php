<?php
namespace UEMC\GoogleDrive;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCGoogleDriveBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}