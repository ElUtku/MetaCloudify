<?php

namespace UEMC\Ftp\Service;


use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpAdapter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Service\CloudService as Core;

class CloudService extends Core
{

    public function login(SessionInterface $session, Request $request): Account
    {
        $account = new Account();
        $account->setPassword($request->get('password'));
        $account->setUser($request->get('userName'));
        $account->setURL($request->get('URL'));
        $account->setPort($request->get('port') ?? 21);
        $account->setLastIp($request->getClientIp());
        $account->setLastSession(new \DateTime);
        $account->setCloud(CloudTypes::FTP->value);

        $accounts=$session->get('accounts');
        $accounts[uniqid()]=get_object_vars($account);
        $session->set('accounts',$accounts);
        return $account;
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

    public function constructFilesystem(Account $account): Filesystem
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

        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray($options));
        $filesystem=new Filesystem($adapter);

        return $filesystem;
    }
}