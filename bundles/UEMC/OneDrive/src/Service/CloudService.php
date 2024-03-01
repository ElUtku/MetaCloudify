<?php

namespace UEMC\OneDrive\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use Microsoft\Graph\Exception\GraphException;
use Psr\Log\LoggerInterface;
use ShitwareLtd\FlysystemMsGraph\Adapter;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;


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