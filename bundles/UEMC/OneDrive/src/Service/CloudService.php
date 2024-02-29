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


use Twig\Token;
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