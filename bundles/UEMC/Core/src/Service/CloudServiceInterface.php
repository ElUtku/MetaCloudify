<?php

namespace UEMC\Core\Service;

use League\Flysystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use UEMC\Core\Entity\Account;

interface CloudServiceInterface
{
    public function constructFilesystem(Account $account): Filesystem;
    public function getFilesystem(): Filesystem;
    public function setFilesystem(Filesystem $filesystem): void;
    public function listDirectory(String $path);
    public function createDir(String $path, String $name);
    public function createFile(String $path, String $name);
    public function delete(String $path);
    public function upload(String $path, UploadedFile $content);
    public function download(String $path, String $name): string|Response;
    public function login(SessionInterface $session, Request $request): Account|\Exception|String;
    public function logout(SessionInterface $session, Request $request): string;
    function arrayToObject($array): Account;

}