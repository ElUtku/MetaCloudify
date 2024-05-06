<?php
namespace MetaCloudify\FtpBundle\Resources\config;

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
            'metacloudify_ftpbundle' => [
                'resource' => '@MetaCloudifyFtpBundle/src/Resources/config/annotations.yaml'
            ]
        ];
        $annotationsConfig = array_merge($annotationsConfig, $newRoute);

        $newAnnotationsConfig = Yaml::dump($annotationsConfig);

        file_put_contents($annotationsConfigFile, $newAnnotationsConfig);

//Twig -------------------------

        $twigConfigFile = $rootDir . '/config/packages/twig.yaml';

        $twigConfig = Yaml::parse(file_get_contents($twigConfigFile));

        $twigConfig['twig']['paths']['%kernel.project_dir%/vendor/MetaCloudify/FtpBundle/src/Resources/views'] = 'MetaCloudifyFtpBundle';

        $newTwigConfig = "\n";
        $newTwigConfig .= Yaml::dump($twigConfig);
        $newTwigConfig .= "\n";

        file_put_contents($twigConfigFile, $newTwigConfig);
    }
}
