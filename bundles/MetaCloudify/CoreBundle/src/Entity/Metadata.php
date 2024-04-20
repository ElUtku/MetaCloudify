<?php

namespace MetaCloudify\CoreBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use MetaCloudify\CoreBundle\Repository\MetadataRepository;

#[ORM\Entity(repositoryClass: MetadataRepository::class)]
class Metadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $virtualName;

    #[ORM\Column(length: 1000)]
    private ?string $path;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $virtualPath;

    #[ORM\Column(length: 50)]
    private ?string $type;
    #[ORM\Column(type:Types::FLOAT, nullable:true)]
    private ?float $size;
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mime_type;
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $lastModified;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $author;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $visibility;

    #[ORM\Column(length: 20, nullable: false)]
    private ?string $status;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?string $extra;

    #[ORM\ManyToOne(cascade: ["persist"], inversedBy: 'metadata' )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account;

    /**
     * @param string|null $name
     * @param string|null $virtualName
     * @param string|null $path
     * @param string|null $virtualPath
     * @param string|null $type
     * @param float|null $size
     * @param string|null $mime_type
     * @param DateTimeInterface|null $lastModified
     * @param string|null $author
     * @param string|null $visibility
     * @param string|null $status
     * @param string|null $extra
     * @param Account|null $account
     */
    public function __construct(?string $name, ?string $virtualName, ?string $path, ?string $virtualPath, ?string $type, ?float $size, ?string $mime_type, ?DateTimeInterface $lastModified, ?string $author, ?string $visibility, ?string $status, ?string $extra, ?Account $account)
    {
        $this->name = $name;
        $this->virtualName = $virtualName;
        $this->path = ($path === '.') ? '' : $path; // Si el directorio es '' dirname suiele devolver '.' y hat que limpiarlo;
        $this->virtualPath = $virtualPath;
        $this->type = $type;
        $this->size = $size;
        $this->mime_type = $mime_type;
        $this->lastModified = $lastModified;
        $this->author = $author;
        $this->visibility = $visibility;
        $this->status = $status;
        $this->extra = $extra;
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

    /**
     * @return float|null
     */
    public function getSize(): ?float
    {
        return $this->size;
    }

    /**
     * @param float|null $size
     */
    public function setSize(?float $size): void
    {
        $this->size = $size;
    }

    /**
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    /**
     * @param string|null $mime_type
     */
    public function setMimeType(?string $mime_type): void
    {
        $this->mime_type = $mime_type;
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

    /**
     * @return string|null
     */
    public function getExtra(): ?string
    {
        return $this->extra;
    }

    /**
     * @param string|null $extra
     */
    public function setExtra(?string $extra): void
    {
        $this->extra = $extra;
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
