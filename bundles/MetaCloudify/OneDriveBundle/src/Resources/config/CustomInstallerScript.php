<?php
namespace MetaCloudify\OneDriveBundle\Resources\config;

use Symfony\Component\Yaml\Yaml;

class CustomInstallerScript
{
    public static function run(): void
    {
//Annotations ----------------------

        $rootDir = dirname(__DIR__, 6);
        $annotationsConfigFile = $rootDir . '/config/routes/annotations.yaml';

        $annotationsConfig = Yaml::parse(file_get_contents($annotationsConfigFile));

        $newRoute = [
            'metacloudify_onedrivebundle' => [
                'resource' => '@MetaCloudifyOneDriveBundle/src/Resources/config/annotations.yaml'
            ]
        ];
        $annotationsConfig = array_merge($annotationsConfig, $newRoute);

        $newAnnotationsConfig = Yaml::dump($annotationsConfig);

        file_put_contents($annotationsConfigFile, $newAnnotationsConfig);
    }
}
