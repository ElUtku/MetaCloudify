<?php

namespace UEMC\Core\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use UEMC\Core\Repository\MetadataRepository;

#[ORM\Entity(repositoryClass: MetadataRepository::class)]
class Metadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $virtualName = null;

    #[ORM\Column(length: 1000)]
    private ?string $path = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $virtualPath = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $lastModified = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $visibility = null;

    #[ORM\Column(length: 20, nullable: false)]
    private ?string $status = null;

    #[ORM\ManyToOne(cascade: ["persist"], inversedBy: 'metadata' )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    /**
     * @param string|null $name
     * @param string|null $virtualName
     * @param string|null $path
     * @param string|null $virtualPath
     * @param string|null $type
     * @param DateTimeInterface|null $lastModified
     * @param string|null $author
     * @param string|null $visibility
     * @param string|null $status
     * @param Account|null $account
     */
    public function __construct(?string $name, ?string $virtualName, ?string $path, ?string $virtualPath, ?string $type, ?DateTimeInterface $lastModified, ?string $author, ?string $visibility, ?string $status, ?Account $account)
    {
        $this->name = $name;
        $this->virtualName = $virtualName;
        $this->path = $path;
        $this->virtualPath = $virtualPath;
        $this->type = $type;
        $this->lastModified = $lastModified;
        $this->author = $author;
        $this->visibility = $visibility;
        $this->status = $status;
        $this->account = $account;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getVirtualName(): ?string
    {
        return $this->virtualName;
    }

    public function setVirtualName(?string $virtualName): static
    {
        $this->virtualName = $virtualName;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getVirtualPath(): ?string
    {
        return $this->virtualPath;
    }

    public function setVirtualPath(?string $virtualPath): static
    {
        $this->virtualPath = $virtualPath;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->lastModified;
    }

    public function setLastModified(DateTimeInterface $lastModified): static
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(?string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }


    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }
}
