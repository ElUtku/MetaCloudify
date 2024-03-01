<?php

namespace UEMC\GoogleDrive\Service;

// include 'vendor/autoload.php';

use Exception;
use Google_Client;
use Google\Service\Drive;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use League\OAuth2\Client\Provider\Google;
use Masbug\Flysystem\GoogleDriveAdapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;
use UEMC\Core\Service\CloudService as Core;
use UEMC\GoogleDrive\Entity\GoogleDriveAccount;

class CloudService extends Core
{
    public function constructFilesystem(GoogleDriveAccount $account)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $client = new Google_Client();
        $client->setClientId($config['clientId']);
        $client->setClientSecret($config['clientSecret']);
        $client->setAccessToken($account->getToken());

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        return $filesystem;
    }
}