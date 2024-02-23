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

use UEMC\Core\Service\CloudService as Core;

class CloudService
{
    private $clientID="423818169355-mjhnjfqs6ue9m2ni9eldol8h8oibgr8f.apps.googleusercontent.com";
    private $clientSecret="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";
    private $redirectUri="http://localhost/cloudBundles5.4/public/index.php/GoogleDrive/login";
    private $redirectUriAccessTap="http://localhost/cloudBundles5.4/public/index.php/GoogleDrive/accessTap";
    private $scopes=[
        'https://www.googleapis.com/auth/drive.appdata',
        'https://www.googleapis.com/auth/drive.appfolder',
        'https://www.googleapis.com/auth/drive.install',
        'https://www.googleapis.com/auth/drive.file',
        'https://www.googleapis.com/auth/drive.readonly',
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/userinfo.profile'
    ];

    static $core;

    public function __construct()
    {
        self::$core = new Core();
    }

    public function getClientID(): string
    {
        return $this->clientID;
    }

    public function setClientID(string $clientID): void
    {
        $this->clientID = $clientID;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUriAccessTap(): string
    {
        return $this->redirectUriAccessTap;
    }

    public function setRedirectUriAccessTap(string $redirectUriAccessTap): void
    {
        $this->redirectUriAccessTap = $redirectUriAccessTap;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setScopes(array $scopes): void
    {
        $this->scopes = $scopes;
    }

    public function auth($session, $request)
    {

        $provider = new Google([
            'clientId'     => $this->getClientID(),
            'clientSecret' => $this->getClientSecret(),
            'redirectUri'  => $this->getRedirectUri(),
        ]);

        if (!empty($request->get('error'))) {

            // Got an error, probably user denied access
            exit('Got error: ' . htmlspecialchars($request->get('error'), ENT_QUOTES, 'UTF-8'));

        } elseif (empty($request->get('code'))) {

            $authUrl = $provider->getAuthorizationUrl([
                'scope' => $this->getScopes(),
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
        $client = new Google_Client();  // Specify the CLIENT_ID of the app that accesses the backend
        $client->setClientId($this->getClientID());

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

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);
        $path = $request->get('path');

        return self::$core->listDirectory($filesystem, $path);
    }

    public function createDirectory($session, $request)
    {

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = $request->get('name');

        return self::$core->createDir($filesystem, $path, $name);

    }

    public function createFile($session, $request)
    {

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = $request->get('name');

        return self::$core->createFile($filesystem,$path,$name);

    }

    public function delete($session, $request)
    {

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');

        return self::$core->delete($filesystem,$path);
    }

    public function upload($session, Request $request)
    {

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {
            return self::$core->upload($filesystem,$path,$content);
        }

        return 'KO';

    }

    public function download($session, $request)
    {

        $client = new Google_Client();
        $client->setClientId($this->getClientID());
        $client->setClientSecret($this->getClientSecret());
        $client->setAccessToken($session->get('googleSession')['token']['access_token']);

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        $path = $request->get('path');
        $name = basename($path);

        return self::$core->download($filesystem,$path,$name);
    }


    public function logout($session, $request)
    {
        $client = new Google_Client(['client_id' => $this->getClientID()]);  // Specify the CLIENT_ID of the app that accesses the backend

        $session->remove('googleSession');

        $client->revokeToken();

        return "Sesion limpia";

    }
}