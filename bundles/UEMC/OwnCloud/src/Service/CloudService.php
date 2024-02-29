<?php

namespace UEMC\OwnCloud\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

use UEMC\Core\Service\CloudService as Core;
use UEMC\OwnCloud\Entity\OwnCloudAccount;


// include 'vendor/autoload.php';

class CloudService extends Core
{

    public function upload(String $path, UploadedFile $content): string
    {

        $filesystem = $this->getFilesystem();


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

    public function constructFilesystem(OwnCloudAccount $account): Filesystem
    {
        $options = [
            'baseUri' => $account->getURL(),
            'userName' => $account->getUser(),
            'password' => $account->getPassword()
        ];

        $client = new Client($options);
        $adapter = new WebDAVAdapter($client);

        return new Filesystem($adapter);

    }

}