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

class CloudService
{


    private Core $core;


    public function __construct()
    {
        $this->core = new Core();
    }

    public function auth($session, $request)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $provider = new Google([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri']
        ]);

        if (!empty($request->get('error'))) {

            // Got an error, probably user denied access
            exit('Got error: ' . htmlspecialchars($request->get('error'), ENT_QUOTES, 'UTF-8'));

        } elseif (empty($request->get('code'))) {

            $authUrl = $provider->getAuthorizationUrl([
                'scope' => $config['scopes'],
            ]);
            $googleSesion['oauth2state']=$provider->getState();
            $session->set('googleSession',$googleSesion);
            header('Location: ' . $authUrl);
            exit();

        } elseif (empty($request->get('state')) || ($request->get('state') !== $session->get('googleSession')['oauth2state'])) {

            // Situacion de ataque CSRF
            $session->remove('googleSession');
            return ('Invalid state');

        } else {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);
            try {
                //$values = $token->getValues();
                //$googleSesion['code']=$request->get('code');
                $googleSesion['token']=$token->jsonSerialize();
                $session->set('googleSession',$googleSesion);
            } catch (Exception $e) {
                return ('Something went wrong: ' . $e->getMessage());
            }
            $googleSesion['googleUser']=$provider->getResourceOwner($token)->toArray();
            $session->set('googleSession',$googleSesion);
            return $token->getToken();
        }
    }

    public function authTap($session, $request)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $client = new Google_Client();  // Specify the CLIENT_ID of the app that accesses the backend
        $client->setClientId($config['clientId']);

        $client->setAccessToken($request->get('credential'));
        $payload = $client->verifyIdToken($request->get('credential'));
        if ($payload) {
            $userid = $payload['sub'];
            $token=$client->getAccessToken();
            //$session->set('accessToken',$token);
            $googleSesion['googleUser']=$payload;
            //$session->set('googleSession',$this->getGoogleSesion());
            //return $payload;
            return $this->auth($session,$request);
        } else {
            return "no funciona";
        }
    }

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