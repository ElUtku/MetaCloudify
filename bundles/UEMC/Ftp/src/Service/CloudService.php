<?php

namespace UEMC\Ftp\Service;


use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpAdapter;

use UEMC\Core\Service\CloudService as Core;
use UEMC\Ftp\Entity\FtpAccount;

class CloudService extends Core
{
    public function constructFilesystem(FtpAccount $account): Filesystem
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