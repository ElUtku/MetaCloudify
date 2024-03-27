<?php

namespace UEMC\Core;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UEMCCoreBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}