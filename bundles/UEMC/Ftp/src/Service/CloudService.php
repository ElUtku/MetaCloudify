<?php

namespace UEMC\Ftp\Service;


use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpAdapter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\Core\Entity\Account;
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

        $ftpAccounts = $session->get('ftpAccounts');
        $ftpAccounts[uniqid()]=get_object_vars($account);
        $session->set('ftpAccounts',$ftpAccounts);
        return $account;
    }

    public function logout(SessionInterface $session, Request $request)
    {
        $ftpAccounts = $session->get('ftpAccounts');

        $id = $request->get('accountId');
        if (array_key_exists($id, $ftpAccounts)) {
            // Eliminar el elemento del array
            unset($ftpAccounts[$id]);
            if (empty($ftpAccounts) || !is_array($ftpAccounts)) {
                // Si está vacío o no es un array, eliminarlo de la sesión
                $session->remove('ftpAccounts');
            }else{
                $session->set('ftpAccounts', $ftpAccounts);
            }
        }
        return "Sesion limpia";
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