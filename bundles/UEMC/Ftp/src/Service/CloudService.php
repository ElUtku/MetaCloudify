<?php

namespace UEMC\Ftp\Service;


use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\Flysystem\Ftp\FtpAdapter;
use Symfony\Component\HttpFoundation\Response;

use UEMC\Core\Service\CloudService as Core;

class CloudService
{

    private Core $core;

    public function __construct()
    {
        $this->core = new Core();
    }


    public function login(SessionInterface $session, Request $request)
    {
        try {
            $options=[
                'host' => $request->get('URL'), // required
                'root' => '/', // required
                'username' => $request->get('userName'), // required
                'password' => $request->get('password'), // required
                'port' => 21,
                'ssl' => false,
                'timeout' => 90,
                'utf8' => false,
                'passive' => true,
                'transferMode' => FTP_BINARY,
                'systemType' => null, // 'windows' or 'unix'
                'ignorePassiveAddress' => null, // true or false
                'timestampsOnUnixListingsEnabled' => false, // true or false
                'recurseManually' => true // true
            ];
            $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($options));

            $filesystem=new Filesystem($adapter);
            $filesystem->listContents('/')
                ->filter(fn(StorageAttributes $attributes) => $attributes->isDir())
                ->toArray();

            $ftpSession['ftpUser']=[
                'user' => $request->get('userName'),
                'password' => $request->get('password'),
                'URL' => $request->get('URL')
            ];
            $ftpSession['options']=$options;
            $session->set('ftpSession',$ftpSession);

            return true;


        } catch (FilesystemException $e)
        {
            return false;
        }
    }

    public function listDirecories(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));

        $path=$request->get('path');

        return $this->core->listDirectory($filesystem,$path);
    }

    public function download(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));
        $path = $request->get('path');
        $name = basename($path);

        return $this->core->download($filesystem,$path,$name);
    }

    public function createDirectory(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createDir($filesystem,$path,$name);

    }

    public function createFile(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));

        $path = $request->get('path');
        $name = $request->get('name');

        return $this->core->createFile($filesystem,$path,$name);

    }

    public function delete(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));

        $path = $request->get('path');

        return $this->core->delete($filesystem,$path);
    }

    public function upload(SessionInterface $session, Request $request)
    {
        $ftpSession=$session->get('ftpSession');
        $filesystem=new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($ftpSession['options'])));

        $path = $request->get('path');

        $content = $request->files->get('content');

        if ($content instanceof UploadedFile) {

            return $this->core->upload($filesystem, $path, $content);

        }
        return 'KO';
    }

    public function logout(SessionInterface $session, Request $request)
    {
        $session->remove('ftpSession');
        return "Sesion limpia";
    }
}