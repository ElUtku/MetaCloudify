<?php

namespace UEMC\OwnCloud\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use phpDocumentor\Reflection\Types\Self_;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use UEMC\Core\Service\CloudService as Core;
use UEMC\OwnCloud\Entity\Owncloud;

use  Psr\Log\LoggerInterface;


// include 'vendor/autoload.php';

class CloudService
{

    private Core $core;
    private Owncloud $owncloud;
    private LoggerInterface $loggerUEMC;


    public function __construct(LoggerInterface $uemcLogger)
    {
        $this->loggerUEMC = $uemcLogger;
        $this->core = new Core();
        $this->owncloud = new Owncloud();
    }


    public function login(SessionInterface $session, Request $request): bool
    {
        $options = [
            'baseUri' => $request->get('URL'),
            'userName' => $request->get('userName'),
            'password' => $request->get('password')
        ];
        $client = new Client($options);
        $adapter = new WebDAVAdapter($client);
        try {
            $filesystem = new Filesystem($adapter);



            $owncloudSession['client']=$options;
            $owncloudSession['ownCloudUser']=[
                'userName'=>$request->get('userName')
            ];
            $session->set('owncloudSession',$owncloudSession);
            return true;
        } catch (\Exception $e) {
            // Si hay una excepción, la conexión falló
            return "KO";
        }
    }

    public function listDirectories(SessionInterface $session, Request $request)
    {
        //$this->owncloud->setPassword($session->get('owncloudSession')['client']['password']);
        $this->loggerUEMC->debug(var_export($this->owncloud,true));
        $filesystem = $this->getFilesystem($session);
        $path=$request->get('path');

        return $this->core->listDirectory($filesystem,$path);
    }

    public function download(SessionInterface $session, Request $request)
    {

        $filesystem = $this->getFilesystem($session);

        $path = $request->get('path');
        $name = basename($path);

        return $this->core->download($filesystem,$path,$name);
    }

    public function createDirectory(SessionInterface $session, Request $request)
    {

        $filesystem = $this->getFilesystem($session);

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createDir($filesystem,$path,$name);
    }

    public function createFile(SessionInterface $session, Request $request)
    {

        $filesystem = $this->getFilesystem($session);

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createFile($filesystem,$path,$name);

    }

    public function delete(SessionInterface $session, Request $request): string
    {

        $filesystem = $this->getFilesystem($session);

        $path = $request->get('path');

        return $this->core->delete($filesystem,$path);
    }
    public function upload(SessionInterface $session, Request $request): string
    {

        $filesystem = $this->getFilesystem($session);

        $path = $request->get('path');
        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {
            $localPath = $content->getPathname();

            try {
                if (!$filesystem->directoryExists($path)) {
                    return 'Error al leer el contenido del archivo.';
                }

                try {
                    $fileContents = file_get_contents($localPath);

                    if ($fileContents !== false) {
                        $filesystem->write($path . '/' . $content->getClientOriginalName(), $fileContents);
                        return 'OK';
                    } else {
                        return 'Error al leer el contenido del archivo.';
                    }
                } catch (FilesystemException | UnableToWriteFile $exception) {
                    return $exception->getMessage();
                }

            } catch (FilesystemException $e) {
                return $e->getMessage();
            }
        }
        return 'KO';
    }
    public function logout($session, $request)
    {
        $session->remove('owncloudSession');
        return "Sesion limpia";
    }

    function getFilesystem(SessionInterface $session): Filesystem
    {
        $client = new Client([
            'baseUri' => $session->get('owncloudSession')['client']['baseUri'],
            'userName' => $session->get('owncloudSession')['client']['userName'],
            'password' => $session->get('owncloudSession')['client']['password']
        ]);

        $adapter = new WebDAVAdapter($client);
        $filesystem = new Filesystem($adapter);

        return $filesystem;
    }
}