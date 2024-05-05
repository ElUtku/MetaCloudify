<?php
namespace MetaCloudify\CoreBundle\Resources\config;

use Symfony\Component\Yaml\Yaml;

class CustomInstallerScript
{
    public static function run(): void
    {
        $rootDir = dirname(__DIR__, 6);
        $annotationsConfigFile = $rootDir . '/config/routes/annotations.yaml';

        $annotationsConfig = Yaml::parse(file_get_contents($annotationsConfigFile));

        $newRoute = [
            'metacloudify_corebundle' => [
                'resource' => '@MetaCloudifyCoreBundle/src/Resources/config/annotations.yaml'
            ]
        ];
        $annotationsConfig = array_merge($annotationsConfig, $newRoute);

        $newAnnotationsConfig = Yaml::dump($annotationsConfig);

        file_put_contents($annotationsConfigFile, $newAnnotationsConfig);

        $twigConfigFile = $rootDir . '/config/packages/twig.yaml';

        $twigConfig = Yaml::parse(file_get_contents($twigConfigFile));

        $twigConfig['twig']['paths'][$rootDir . '/bundles/MetaCloudify/OwnCloudBundle/src/Resources/views'] = 'MetaCloudifyOwnCloudBundle';

        $newTwigConfig = Yaml::dump($twigConfig);

        file_put_contents($twigConfigFile, $newTwigConfig);
    }
}
