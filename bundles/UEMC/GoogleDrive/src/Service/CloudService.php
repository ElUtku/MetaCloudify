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

class CloudService extends Core
{

    public function listDirectories($session, $request)
    {

        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);
        $path = $request->get('path');

        return $this->core->listDirectory($filesystem, $path);
    }

    public function createDirectory($session, $request)
    {

        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createDir($filesystem, $path, $name);

    }

    public function createFile($session, $request)
    {

        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createFile($filesystem,$path,$name);

    }

    public function delete($session, $request)
    {

        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');

        return $this->core->delete($filesystem,$path);
    }

    public function upload(SessionInterface $session, Request $request)
    {
        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {
            return $this->core->upload($filesystem,$path,$content);
        }

        return 'KO';

    }

    public function download(SessionInterface $session, $request)
    {
        $client = $this->getClient($session);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = basename($path);

        return $this->core->download($filesystem,$path,$name);
    }


    public function logout($session, $request)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $client = new Google_Client(['client_id' => $config['clientId']]);  // Specify the CLIENT_ID of the app that accesses the backend

        $session->remove('googleSession');

        $client->revokeToken();

        return "Sesion limpia";

    }

    private function getClient(SessionInterface $session)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $client = new Google_Client();
        $client->setClientId($config['clientId']);
        $client->setClientSecret($config['clientSecret']);
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        return $client;
    }
}