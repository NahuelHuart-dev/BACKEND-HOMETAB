<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad Task.
 * 
 * Representa una tarea dentro de un hogar, asignada a un usuario específico.
 * Contiene detalles como el título, descripción, prioridad, fecha de vencimiento y estado de completado.
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dueDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['Baixa', 'Mitja', 'Alta'], message: 'La prioritat ha de ser Baixa, Mitja o Alta')]
    private ?string $priority = 'Mitja';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private ?bool $completed = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $disabledAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $assignedTo = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $periodicity = null;

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    /** Guarda la periodicidad textual usada por pantallas antiguas o futuras. */
    public function setPeriodicity(?string $periodicity): static
    {
        $this->periodicity = $periodicity;

        return $this;
    }



    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único de la tarea.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    /** Marca la tarea como completada o pendiente. */
    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDisabledAt(): ?\DateTimeInterface
    {
        return $this->disabledAt;
    }

    public function setDisabledAt(?\DateTimeInterface $disabledAt): static
    {
        $this->disabledAt = $disabledAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->disabledAt = $isActive ? null : ($this->disabledAt ?? new \DateTime());

        return $this;
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

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    /** Asigna la tarea a un miembro de la casa. */
    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }
    public function setDueDate(?\DateTime $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }
    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }
    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }
}
