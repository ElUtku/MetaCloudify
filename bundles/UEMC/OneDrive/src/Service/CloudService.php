<?php

namespace UEMC\OneDrive\Service;

use Exception;

use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Microsoft\Graph\Exception\GraphException;
use ShitwareLtd\FlysystemMsGraph\Adapter;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use UEMC\Core\Service\CloudService as Core;

class CloudService
{
    static $core;

    public function __construct()
    {
        self::$core = new Core();
    }

    public function listDirectories($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');
        $filesystem = new Filesystem($adapter);
        $path = $request->get('path');

        return self::$core->listDirectory($filesystem,$path);
    }

    public function download($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');

        $filesystem = new Filesystem($adapter);

        $path = $request->get('path');
        $name = basename($path);

        return self::$core->download($filesystem,$path,$name);
    }

    public function createDirectory($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');

        $filesystem = new Filesystem($adapter);

        $path = $request->get('path');
        $name = $request->get('name');

        return self::$core->createDir($filesystem,$path,$name);
    }

    public function createFile($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');

        $filesystem = new Filesystem($adapter);

        $path = $request->get('path');
        $name = $request->get('name');
        $newPath = '/'.$path. '/'. $name;
        try {
            if($filesystem->directoryExists($path)) //Comprobamos si existe el directorio
            {
                if($filesystem->directoryExists($newPath))
                {
                    return  "El fichero ya existe";
                } else{
                    $filesystem->write($newPath,"");
                    return  "Fichero creado";
                }
            }else{
                $message ='El directorio no existe ';
                return $message.' | Fichero NO creado';
            }
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            return $exception;
        }

    }

    public function delete($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');

        $filesystem = new Filesystem($adapter);

        $path = $request->get('path');

        return self::$core->delete($filesystem,$path);
    }

    public function upload($session,Request $request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');

        $filesystem = new Filesystem($adapter);

        $path = $request->get('path');
        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {

            return self::$core->upload($filesystem, $path, $content);

        }

        return 'KO';

    }

    public function token($session,$request)
    {
        $provider = new Microsoft([
            // Required
            'clientId'                  => '84d85895-150a-47cb-92a1-4484f034dbac',
            'clientSecret'              => '_FC8Q~xVOOcc0EzeH1eCWHSpFaiDdTFvt1cm0aYs',
            'redirectUri'               => 'http://localhost/cloudBundles5.4/public/index.php/onedrive/access',
            // Optional
            'urlAuthorize'              => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'urlAccessToken'            => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'urlResourceOwnerDetails'   => 'https://outlook.office.com/api/v2.0/me'
        ]);
        $options = [
                    'scope' => ['User.Read', 'openid', 'Files.ReadWrite.All','offline_access','Mail.Read'] // array or string
        ];

        if (empty($request->get('code'))) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl($options);
            $onedriveSession['oauth2state']=$provider->getState();
            $session->set('onedriveSession',$onedriveSession);
            header('Location: '.$authUrl);
            exit();
// Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($request->get('state')) || ($request->get('state') !== $session->get('onedriveSession')['oauth2state'])) {

            $session->remove('onedriveSession');
            return('Invalid state');

        } else {
            try {
                //$session->set('code',$request->get('code'));
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $onedriveSession['token']=$token->jsonSerialize();
                $session->set('onedriveSession',$onedriveSession);

                //$session->set('user',$this->getUserInfo($request->get('code')));

            } catch (Exception $e) {
                return($e);
            }
            return "OK";
        }
    }

    public function getUserInfo($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $graph = new Graph();
        $graph->setAccessToken($onedriveSession['token']['access_token']);

        try {
            $user = $graph->createRequest('GET', '/me')
                ->setReturnType(Model\User::class)
                ->execute();
            $onedriveSession['oneDriveUser']=json_decode(json_encode($user), true);
            $session->set('onedriveSession',$onedriveSession);
            return "OK";
        } catch (GuzzleException|GraphException $e) {
            return $e;
        }

    }


    public function logout($session, $request)
    {
        $session->remove('onedriveSession');
        return "Sesion limpia";
    }
}