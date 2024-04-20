<?php

namespace MetaCloudify\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MetaCloudifyCoreBundle extends Bundle
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}