<?php

namespace MetaCloudify\OwnCloudBundle\Service;

use DateTime;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use MetaCloudify\CoreBundle\Entity\Account;
use MetaCloudify\CoreBundle\Resources\CloudTypes;
use MetaCloudify\CoreBundle\Resources\ErrorTypes;
use MetaCloudify\CoreBundle\Service\CloudException;
use MetaCloudify\CoreBundle\Service\CloudService as Core;


// include 'vendor/autoload.php';

class CloudService extends Core
{

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account
     * @throws CloudException
     */
    public function login(SessionInterface $session, Request $request): Account
    {

        $account = new Account();

        $account->setPassword($request->getPassword());
        $account->setUser($request->getUser());
        $account->setURL($request->get('url'));
        $account->setPort($request->get('port') ?? 443);
        $account->setLastIp($request->getClientIp());
        $account->setLastSession(new DateTime);
        $account->setCloud(CloudTypes::OwnCloud->value);

        $this->testConection($account);

        return $account;
    }

    /**
     *
     * Convierte un array en un objeto de tipo Account
     *
     * @param $array
     * @return Account
     * @throws CloudException
     */
    function arrayToObject($array): Account
    {
        try {
            $account = new Account();
            foreach ($array as $key => $value) {
                if (property_exists($account, $key)) {
                    $account->$key = $value;
                }
            }
            return $account;
        } catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorMessage().' - '.$e->getMessage(),
                                     ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorCode());
        }

    }


    /**
     *
     * Metodo personalizado que sobreescribe el original para subir archivos a webdav.
     *
     * @param String $path
     * @param UploadedFile $content
     * @return void
     * @throws CloudException
     */
    public function upload(String $path, UploadedFile $content): void
    {

        $filesystem = $this->getFilesystem();


        $localPath = $content->getPathname();

        try {
            if (!$filesystem->directoryExists($path)) {
                throw new CloudException(ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorMessage().' - '.$path,
                                          ErrorTypes::DIRECTORIO_NO_EXISTE->getErrorCode());
            }

            try {
                $fileContents = file_get_contents($localPath);

                if ($fileContents !== false) {
                    $filesystem->write($path . '/' . $content->getClientOriginalName(), $fileContents);
                } else {
                    throw new CloudException(ErrorTypes::BAD_CONTENT->getErrorMessage(),
                        ErrorTypes::BAD_CONTENT->getErrorCode());                }
            } catch (FilesystemException | UnableToWriteFile $e) {
                throw new CloudException(ErrorTypes::ERROR_UPLOAD->getErrorMessage().' - '.$e->getMessage(),
                                         ErrorTypes::ERROR_UPLOAD->getErrorCode());
            }

        } catch (FilesystemException $e) {
            throw new CloudException(ErrorTypes::ERROR_UPLOAD->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_UPLOAD->getErrorCode());
        }
    }

    /**
     * @param Account $account
     * @return Filesystem
     * @throws CloudException
     */
    public function constructFilesystem(Account $account): Filesystem
    {
        try {
            $options = [
                'baseUri' => $account->getURL(),
                'userName' => $account->getUser(),
                'password' => $account->getPassword()
            ];

            $client = new Client($options);

            $adapter = new WebDAVAdapter($client);

            return new Filesystem($adapter);
        }catch (Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorCode());
        }
    }

    /**
     * @param Account $account
     * @return void
     * @throws CloudException
     */
    public function testConection(Account $account): void
    {
        $options = [
            'baseUri' => $account->getURL(),
            'userName' => $account->getUser(),
            'password' => $account->getPassword()
        ];

        $client = new Client($options);
        try {
            $response = $client->request('GET');

            $httpCode = $response['statusCode'];

            if ($httpCode != 200) {
                throw new CloudException(ErrorTypes::ERROR_CREDENTIALS->getErrorMessage(),
                    ErrorTypes::ERROR_CREDENTIALS->getErrorCode());
            }
        } catch (CloudException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new CloudException(ErrorTypes::URL_FAIL->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::URL_FAIL->getErrorCode());
        }
    }
}