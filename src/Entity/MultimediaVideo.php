<?php

namespace App\Entity;

use App\Repository\MultimediaVideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad MultimediaVideo.
 * 
 * Representa un video multimedia (por ejemplo, de YouTube) añadido a una lista de reproducción (MultimediaPlaylist).
 * Contiene información del video como el ID externo, título, canal y posición en la lista.
 */
#[ORM\Entity(repositoryClass: MultimediaVideoRepository::class)]
class MultimediaVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MultimediaPlaylist $playlist = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $addedBy = null;

    #[ORM\Column(length: 32)]
    private string $youtubeId = '';

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $channelTitle = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único del video.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?MultimediaPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(?MultimediaPlaylist $playlist): static
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(?User $addedBy): static
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    public function getYoutubeId(): string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(string $youtubeId): static
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getChannelTitle(): ?string
    {
        return $this->channelTitle;
    }

    public function setChannelTitle(?string $channelTitle): static
    {
        $this->channelTitle = $channelTitle;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
