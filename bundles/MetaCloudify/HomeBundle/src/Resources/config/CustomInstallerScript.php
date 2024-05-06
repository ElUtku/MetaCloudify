<?php
namespace MetaCloudify\HomeBundle\Resources\config;

use Symfony\Component\Yaml\Yaml;

class CustomInstallerScript
{
    public static function run(): void
    {
        $rootDir = dirname(__DIR__, 6);
        require_once $rootDir.'/vendor/autoload.php';

//Annotations ----------------------

        $annotationsConfigFile = $rootDir . '/config/routes/annotations.yaml';

        $annotationsConfig = Yaml::parse(file_get_contents($annotationsConfigFile));

        $newRoute = [
            'metacloudify_homebundle' => [
                'resource' => '@MetaCloudifyHomeBundle/src/Resources/config/annotations.yaml'
            ]
        ];
        $annotationsConfig = array_merge($annotationsConfig, $newRoute);

        $newAnnotationsConfig = Yaml::dump($annotationsConfig);

        file_put_contents($annotationsConfigFile, $newAnnotationsConfig);

//Twig -------------------------

        $twigConfigFile = $rootDir . '/config/packages/twig.yaml';

        $twigConfig = Yaml::parse(file_get_contents($twigConfigFile));
        $twigConfig['twig']['paths']['%kernel.project_dir%/vendor/MetaCloudify/HomeBundle/src/Resources/views'] = 'MetaCloudifyHomeBundle';

        $newTwigConfig = "\n";
        $newTwigConfig .= Yaml::dump($twigConfig);
        $newTwigConfig .= "\n";

        file_put_contents($twigConfigFile, $newTwigConfig);
    }
}
