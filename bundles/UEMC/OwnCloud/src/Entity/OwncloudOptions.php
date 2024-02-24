<?php

namespace UEMC\OwnCloud\Entity;

use UEMC\OwnCloud\Repository\OwncloudOptionsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OwncloudOptionsRepository::class)]
class OwncloudOptions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $id_account = null;

    #[ORM\Column(length: 1000)]
    private ?string $url = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $port = null;

    #[ORM\Column(length: 1000)]
    private ?string $ruta_raiz = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAccount(): ?int
    {
        return $this->id_account;
    }

    public function setIdAccount(int $id_account): static
    {
        $this->id_account = $id_account;

        return $this;
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

    public function getRutaRaiz(): ?string
    {
        return $this->ruta_raiz;
    }

    public function setRutaRaiz(string $ruta_raiz): static
    {
        $this->ruta_raiz = $ruta_raiz;

        return $this;
    }
}
