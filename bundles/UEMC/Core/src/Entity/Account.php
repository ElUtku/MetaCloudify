<?php

namespace UEMC\Core\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UEMC\Core\Repository\AccountRepository;
use UEMC\Core\Service\UemcLogger;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $openid = null;

    #[ORM\Column(length: 16, nullable: false)]
    public ?string $last_ip = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    public ?Datetime $last_session = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(?string $openid): static
    {
        $this->openid = $openid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastIp(): ?string
    {
        return $this->last_ip;
    }

    /**
     * @param string|null $last_ip
     */
    public function setLastIp(?string $last_ip): void
    {
        $this->last_ip = $last_ip;
    }

    /**
     * @return DateTime|null
     */
    public function getLastSession(): ?DateTime
    {
        return $this->last_session;
    }

    /**
     * @param DateTime|null $last_session
     */
    public function setLastSession(?DateTime $last_session): void
    {
        $this->last_session = $last_session;
    }


}
