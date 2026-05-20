<?php

namespace App\Entity;

use App\Repository\MultimediaPlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad MultimediaPlaylist.
 * 
 * Representa una lista de reproducción multimedia dentro de un hogar.
 * Agrupa varios videos (MultimediaVideo) y pertenece a un hogar y a un usuario creador.
 */
#[ORM\Entity(repositoryClass: MultimediaPlaylistRepository::class)]
class MultimediaPlaylist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Household $household = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $createdBy = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, MultimediaVideo> */
    #[ORM\OneToMany(targetEntity: MultimediaVideo::class, mappedBy: 'playlist', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $videos;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->videos = new ArrayCollection();
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único de la lista de reproducción.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): static
    {
        $this->household = $household;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, MultimediaVideo> */
    public function getVideos(): Collection
    {
        return $this->videos;
    }
}
