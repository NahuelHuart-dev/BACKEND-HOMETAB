<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad HouseholdMember.
 * 
 * Representa la relación entre un usuario y un hogar (Household), determinando
 * su rol ('owner', 'admin', 'member') dentro de esa casa.
 */
#[ORM\Entity]
#[ORM\Table(name: 'home_members')] // Respetamos el nombre de tu PDF
class HouseholdMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'householdMemberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Household $household = null;

    #[ORM\Column(length: 20)]
    private ?string $role = 'member'; // 'owner', 'admin', 'member'

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    // --- GETTERS Y SETTERS ---
    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getHousehold(): ?Household { return $this->household; }
    public function setHousehold(?Household $household): self { $this->household = $household; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getJoinedAt(): ?\DateTimeImmutable { return $this->joinedAt; }
    public function setJoinedAt(\DateTimeImmutable $joinedAt): self { $this->joinedAt = $joinedAt; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }
}
