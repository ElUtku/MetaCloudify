<?php

namespace UEMC\OneDrive\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use Microsoft\Graph\Exception\GraphException;
use Psr\Log\LoggerInterface;
use ShitwareLtd\FlysystemMsGraph\Adapter;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;


use UEMC\Core\Service\CloudService as Core;

class CloudService
{
    private Core $core;
    private LoggerInterface $loggerUEMC;

    public function __construct(LoggerInterface $uemcLogger)
    {
        $this->loggerUEMC = $uemcLogger;
        $this->core = new Core();
    }

    public function listDirectories($session,$request)
    {
        $filesystem=$this->getFileSystem($session,$request);

        $path = $request->get('path');

        return $this->core->listDirectory($filesystem,$path);
    }

    public function download($session,$request)
    {
        $filesystem=$this->getFileSystem($session,$request);

        $path = $request->get('path');
        $name = basename($path);

        return $this->core->download($filesystem,$path,$name);
    }

    public function createDirectory($session,$request)
    {
        $filesystem=$this->getFileSystem($session,$request);

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createDir($filesystem,$path,$name);
    }

    public function createFile($session,$request)
    {
        $filesystem=$this->getFileSystem($session,$request);

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
        $filesystem=$this->getFileSystem($session,$request);

        $path = $request->get('path');

        return $this->core->delete($filesystem,$path);
    }

    public function upload($session,Request $request)
    {
        $filesystem=$this->getFileSystem($session,$request);

        $path = $request->get('path');
        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {

            return $this->core->upload($filesystem, $path, $content);

        }

        return 'KO';

    }

    public function token($session,$request)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\onedrive.yaml');

        $provider = new Microsoft([
            // Required
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUri'],
            'urlAuthorize'            => $config['urlAuthorize'],
            'urlAccessToken'          => $config['urlAccessToken'],
            'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
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

    public function logout($session, $request): string
    {
        $session->remove('onedriveSession');
        return "Sesion limpia";
    }

    private function getFileSystem(SessionInterface $session, Request $request): Filesystem
    {
        $onedriveSession=$session->get('onedriveSession');
        $access_token= $request->get('access_token') ?? $onedriveSession['token']['access_token']; //Si se proporciona un access_token se usa ese si no, el de la seision

        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');
        return new Filesystem($adapter);
    }
}