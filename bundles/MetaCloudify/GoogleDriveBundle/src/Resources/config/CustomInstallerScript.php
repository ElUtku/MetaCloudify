<?php
namespace MetaCloudify\GoogleDriveBundle\Resources\config;

class CustomInstallerScript
{
    public static function run(): void
    {
        $rootDir = dirname(__DIR__, 6);
        $file = $rootDir . '/config/routes/annotations.yaml';
        $lineToAdd = "\n" .
            "\nmetacloudify_googledrivebundle:" .
            "\n    resource: '@MetaCloudifyGoogleDriveBundle/src/Resources/config/annotations.yaml'" .
            "\n";

        file_put_contents($file, $lineToAdd, FILE_APPEND);
    }
}
