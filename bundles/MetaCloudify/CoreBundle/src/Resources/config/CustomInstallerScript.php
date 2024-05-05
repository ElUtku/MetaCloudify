<?php

namespace MetaCloudify\CoreBundle\Resources\config;

class CustomInstallerScript
{
    public static function run(): void
    {
        $file = __DIR__ . '/../../../../../config/routes/annotations.yaml';
        $lineToAdd = "metacloudify_corebundle:\n    resource: '@MetaCloudifyCoreBundle/src/Resources/config/annotations.yaml'\n";

        file_put_contents($file, $lineToAdd, FILE_APPEND);
    }
}
