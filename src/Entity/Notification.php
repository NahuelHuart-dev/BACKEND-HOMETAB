<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad Notification.
 * 
 * Representa una notificación enviada a un usuario dentro de un hogar.
 * Puede ser de diferentes tipos (tarea, evento, gasto, etc.) y tiene un estado de lectura.
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_notification_key', fields: ['notificationKey'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Household $household = null;

    #[ORM\Column(length: 180)]
    private ?string $notificationKey = null;

    #[ORM\Column(length: 30)]
    private string $type = 'task';

    #[ORM\Column(length: 20)]
    private string $priority = 'normal';

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $targetAt = null;

    #[ORM\Column(length: 255)]
    private ?string $route = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $targetType = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetId = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $readAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único de la notificación.
     */
    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getHousehold(): ?Household { return $this->household; }
    public function setHousehold(?Household $household): static { $this->household = $household; return $this; }
    public function getNotificationKey(): ?string { return $this->notificationKey; }
    public function setNotificationKey(string $notificationKey): static { $this->notificationKey = $notificationKey; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getPriority(): string { return $this->priority; }
    public function setPriority(string $priority): static { $this->priority = $priority; return $this; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }
    public function getTargetAt(): ?\DateTimeInterface { return $this->targetAt; }
    public function setTargetAt(?\DateTimeInterface $targetAt): static { $this->targetAt = $targetAt; return $this; }
    public function getRoute(): ?string { return $this->route; }
    public function setRoute(string $route): static { $this->route = $route; return $this; }
    public function getTargetType(): ?string { return $this->targetType; }
    public function setTargetType(?string $targetType): static { $this->targetType = $targetType; return $this; }
    public function getTargetId(): ?int { return $this->targetId; }
    public function setTargetId(?int $targetId): static { $this->targetId = $targetId; return $this; }
    public function isRead(): bool { return $this->isRead; }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        $this->readAt = $isRead ? ($this->readAt ?? new \DateTime()) : null;

        return $this;
    }

    public function getReadAt(): ?\DateTimeInterface { return $this->readAt; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
