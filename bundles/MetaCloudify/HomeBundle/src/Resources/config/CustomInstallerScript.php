<?php
namespace MetaCloudify\HomeBundle\Resources\config;

class CustomInstallerScript
{
    public static function run(): void
    {
        $rootDir = dirname(__DIR__, 6);
        $file = $rootDir . '/config/routes/annotations.yaml';
        $lineToAdd = "\n" .
            "\nmetacloudify_corebundle:" .
            "\n    resource: '@MetaCloudifyHomeBundle/src/Resources/config/annotations.yaml'" .
            "\n";

        file_put_contents($file, $lineToAdd, FILE_APPEND);
    }
}
