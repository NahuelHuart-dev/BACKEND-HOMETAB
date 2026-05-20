<?php

namespace App\Entity;

use App\Repository\ExpenseShareRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad ExpenseShare.
 * 
 * Representa la parte de un gasto compartido que corresponde a un usuario concreto dentro de un hogar.
 * Indica la cantidad a deber, si ya ha sido pagada y la fecha de pago.
 */
#[ORM\Entity(repositoryClass: ExpenseShareRepository::class)]
class ExpenseShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'expenseShares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Expense $expense = null;

    #[ORM\ManyToOne(inversedBy: 'expenseShares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amountOwed = null;

    #[ORM\Column]
    private ?bool $isPaid = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExpense(): ?Expense
    {
        return $this->expense;
    }

    public function setExpense(?Expense $expense): static
    {
        $this->expense = $expense;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAmountOwed(): ?string
    {
        return $this->amountOwed;
    }

    public function setAmountOwed(string $amountOwed): static
    {
        $this->amountOwed = $amountOwed;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    /** Marca si este usuario ya pago su parte. */
    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }
}
