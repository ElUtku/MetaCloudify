<?php

namespace UEMC\Ftp\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\StorageAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\Core\Entity\Account;
use UEMC\Ftp\Repository\FtpAccountRepository;

#[ORM\Entity(repositoryClass: FtpAccountRepository::class)]
class FtpAccount extends Account
{
    #[ORM\Column(length: 1000)]
    public ?string $url = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $port = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }


    public function login(SessionInterface $session, Request $request)
    {
        $this->setPassword($request->get('password'));
        $this->setUser($request->get('userName'));
        $this->setURL($request->get('URL'));
        $this->setPort($request->get('port') ?? 21);

        $ftpAccounts = $session->get('ftpAccounts');
        $ftpAccounts[uniqid()]=get_object_vars($this);
        $session->set('ftpAccounts',$ftpAccounts);
        return true;
    }

    public function logout(SessionInterface $session, Request $request)
    {
        $ftpAccounts = $session->get('ftpAccounts');

        $id = $request->get('id');
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

    function arrayToObject($array): FtpAccount
    {
        $object = new FtpAccount();
        foreach ($array as $key => $value) {
            if (property_exists($object, $key)) {
                $object->$key = $value;
            }
        }
        return $object;
    }
}
