<?php

namespace MetaCloudify\Ftp\Service;


use DateTime;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\ConnectivityChecker;
use League\Flysystem\Ftp\FtpConnectionException;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionProvider;
use League\Flysystem\Ftp\NoopCommandConnectivityChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use MetaCloudify\Core\Entity\Account;
use MetaCloudify\Core\Resources\CloudTypes;
use MetaCloudify\Core\Resources\ErrorTypes;
use MetaCloudify\Core\Service\CloudException;
use MetaCloudify\Core\Service\CloudService as Core;

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
        $account->setPort($request->get('port') ?? 21);
        $account->setLastIp($request->getClientIp());
        $account->setLastSession(new DateTime);
        $account->setCloud(CloudTypes::FTP->value);

        $this->testConection($account);

        return $account;
    }

    /**
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
        } catch (Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorCode());
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
            $options=[
                'host' => $account->getUrl(), // required
                'root' => '/', // required
                'username' => $account->getUser(), // required
                'password' => $account->getPassword(), // required
                'port' => $account->getPort(),
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
            return new Filesystem($adapter);

        } catch (Exception $e) {
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
        $options=[
            'host' => $account->getUrl(), // required
            'root' => '/', // required
            'username' => $account->getUser(), // required
            'password' => $account->getPassword(), // required
            'port' => $account->getPort(),
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

        $provider = new FtpConnectionProvider();
        try {
            $provider->createConnection(FtpConnectionOptions::fromArray($options));
        } catch (FtpConnectionException $e) {
            throw new CloudException($e->getMessage(),
                ErrorTypes::ERROR_INICIO_SESION->getErrorCode(),$e);
        }
    }
}