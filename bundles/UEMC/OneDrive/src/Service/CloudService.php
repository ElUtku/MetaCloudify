<?php

namespace UEMC\OneDrive\Service;

use League\Flysystem\Filesystem;
use ShitwareLtd\FlysystemMsGraph\Adapter;
use Microsoft\Graph\Graph;


use Twig\Token;
use UEMC\Core\Service\CloudService as Core;
use UEMC\OneDrive\Entity\OneDriveAccount;

class CloudService extends Core
{

    public function constructFilesystem(OneDriveAccount $account): Filesystem
    {
        $access_token= $account->getToken();
        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');
        return new Filesystem($adapter);
    }

}