<?php

namespace UEMC\Ftp\Service;


use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpAdapter;
use PHPUnit\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Service\CloudException;
use UEMC\Core\Service\CloudService as Core;

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
        try {
            $account = new Account();
            $account->setPassword($request->get('password'));
            $account->setUser($request->get('userName'));
            $account->setURL($request->get('URL'));
            $account->setPort($request->get('port') ?? 21);
            $account->setLastIp($request->getClientIp());
            $account->setLastSession(new \DateTime);
            $account->setCloud(CloudTypes::FTP->value);

            return $account;
        }catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_INICIO_SESION->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_INICIO_SESION->getErrorCode());
        }

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
        } catch (\Exception $e) {
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

        } catch (\Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorCode());
        }

    }
}