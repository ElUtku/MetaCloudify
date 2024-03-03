<?php

namespace UEMC\OwnCloud\Service;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Service\CloudService as Core;


// include 'vendor/autoload.php';

class CloudService extends Core
{

    public function login(SessionInterface $session, Request $request): Account|\Exception|String
    {
        $account = new Account();

        $account->setPassword($request->get('password'));
        $account->setUser($request->get('userName'));
        $account->setURL($request->get('URL'));
        $account->setPort($request->get('port') ?? '');
        $account->setLastIp($request->getClientIp());
        $account->setLastSession(new \DateTime);
        $account->setCloud(CloudTypes::OwnCloud->value);

        try {

            $this->setSession($session, $account);

            return $account;

        } catch (\Exception $e) {
            return $e;
        }
    }

    function arrayToObject($array): Account
    {
        $account = new Account();
        foreach ($array as $key => $value) {
            if (property_exists($account, $key)) {
                $account->$key = $value;
            }
        }
        return $account;
    }

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

    public function constructFilesystem(Account $account): Filesystem
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