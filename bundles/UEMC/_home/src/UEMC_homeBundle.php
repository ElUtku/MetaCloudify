<?php
namespace UEMC\_home;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMC_homeBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}