<?php
namespace UEMC\OneDrive;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCOneDriveBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}