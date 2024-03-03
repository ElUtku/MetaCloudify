<?php

namespace UEMC\OwnCloud\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use UEMC\Core\Entity\Account;
use UEMC\OwnCloud\Repository\OwnCloudAccountRepository;

#[ORM\Entity(repositoryClass: OwnCloudAccountRepository::class)]
class OwnCloudAccount extends Account
{

    #[ORM\Column(length: 1000)]
    public ?string $URL = null;

    #[ORM\Column(type: 'smallint')]
    public ?int $port = null;

    #[ORM\Column(length: 1000)]
    public ?string $password = null;

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $URL): static
    {
        $this->URL = $URL;

        return $this;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }


    public function login(SessionInterface $session, Request $request): bool
    {
        $this->setPassword($request->get('password'));
        $this->setUser($request->get('userName'));
        $this->setURL($request->get('URL'));
        $this->setPort($request->get('port') ?? '');
        $this->setLastIp($request->getClientIp());

        try {
            $owncloudAccounts = $session->get('owncloudAccounts');
            $owncloudAccounts[uniqid()]=get_object_vars($this);
            $session->set('owncloudAccounts',$owncloudAccounts);
            return true;
        } catch (\Exception $e) {
            // Si hay una excepción, la conexión falló
            return false;
        }
    }
    public function logout(SessionInterface $session, Request $request)
    {
        $owncloudAccounts = $session->get('owncloudAccounts');

        $id = $request->get('accountId');
        if (array_key_exists($id, $owncloudAccounts)) {
            // Eliminar el elemento del array
            unset($owncloudAccounts[$id]);
            if (empty($owncloudAccounts) || !is_array($owncloudAccounts)) {
                // Si está vacío o no es un array, eliminarlo de la sesión
                $session->remove('owncloudAccounts');
            }else{
                $session->set('owncloudAccounts', $owncloudAccounts);
            }
        }
        return "Sesion limpia";
    }

    function arrayToObject($array): OwnCloudAccount
    {
        $object = new OwnCloudAccount();
        foreach ($array as $key => $value) {
            if (property_exists($object, $key)) {
                $object->$key = $value;
            }
        }
        return $object;
    }
}
