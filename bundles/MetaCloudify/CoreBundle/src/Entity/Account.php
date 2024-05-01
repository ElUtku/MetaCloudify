<?php

namespace MetaCloudify\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use MetaCloudify\CoreBundle\Repository\AccountRepository;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    public ?string $cloud = null;

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

    #[ORM\Column(length: 1000, nullable: true)]
    public ?string $URL = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    public ?int $port = null;

    public ?string $token = null;
    public ?string $password = null;

    #[ORM\OneToMany(mappedBy: 'account', targetEntity: Metadata::class)]
    private Collection $metadata;

    public function __construct()
    {
        $this->metadata = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id=$id;
    }

    /**
     * @return string|null
     */
    public function getCloud(): ?string
    {
        return $this->cloud;
    }

    /**
     * @param string|null $cloud
     */
    public function setCloud(?string $cloud): void
    {
        $this->cloud = $cloud;
    }


    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @param string|null $user
     * @return $this
     */
    public function setUser(?string $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return $this
     */
    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    /**
     * @param string|null $openid
     * @return $this
     */
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

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string|null
     */
    public function getURL(): ?string
    {
        return $this->URL;
    }

    /**
     * @param string|null $URL
     */
    public function setURL(?string $URL): void
    {
        $this->URL = $URL;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return $this
     */
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

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }

    public function addMetadata(Metadata $metadata): static
    {
        if (!$this->metadata->contains($metadata)) {
            $this->metadata->add($metadata);
            $metadata->setAccount($this);
        }

        return $this;
    }

    public function removeMetadata(Metadata $metadata): static
    {
        if ($this->metadata->removeElement($metadata)) {
            if ($metadata->getAccount() === $this) {
                $metadata->setAccount(null);
            }
        }
        return $this;
    }
}
