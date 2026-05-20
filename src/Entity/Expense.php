<?php

namespace App\Entity;

use App\Repository\ExpenseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entidad Expense.
 * 
 * Representa un gasto asociado a un hogar. Soporta pagos compartidos o individuales,
 * incluye información sobre el creador, quién pagó, montos, cuotas y periodicidad.
 */
#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'El títol de la despesa no pot estar buit')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'El títol ha de tenir almenys {{ limit }} caràcters'
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'L\'import és obligatori')]
    #[Assert\Positive(message: 'L\'import ha de ser positiu')]
    private ?string $amount = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    /** Tipo de pago: shared para conjunto, individual para una sola persona. */
    private string $paymentType = 'shared';

    #[ORM\ManyToOne(inversedBy: 'expensesPaid')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $paidBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\ManyToOne(inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Household $household = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $periodicity = null;

    #[ORM\Column(nullable: true)]
    /** Dia del mes usado cuando la periodicidad es mensual. */
    private ?int $recurrenceDayOfMonth = null;

    #[ORM\Column(nullable: true)]
    /** Dia de la semana usado cuando la periodicidad es semanal. */
    private ?int $recurrenceWeekday = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    /** Hora usada cuando la periodicidad es diaria. */
    private ?\DateTimeInterface $recurrenceTime = null;

    /**
     * @var Collection<int, ExpenseShare>
     */
    #[ORM\OneToMany(mappedBy: 'expense', targetEntity: ExpenseShare::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $expenseShares;

    #[ORM\Column]
    private ?bool $isPaid = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $disabledAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    public function __construct()
    {
        $this->expenseShares = new ArrayCollection();
        $this->isPaid = false;
        $this->createdAt = new \DateTime();
    }

    /**
     * Import mitjà per persona quan hi ha quotes iguals entre participants actius.
     */
    public function getAmountPerPerson(int $totalPeople): float
    {
        if ($totalPeople === 0) {
            return 0;
        }

        return (float) $this->amount / $totalPeople;
    }

    /** Mitjana segons quota guardada si no es passa cap nombre (API). */
    public function resolveAverageShareAmount(): float
    {
        $n = $this->expenseShares->count();
        if ($n === 0) {
            return 0.0;
        }

        return round((float) $this->amount / $n, 2);
    }

    /** Tots els participants tenen quota i l'han marcat com a pagada. */
    public function isFullyPaidByAllParticipants(): bool
    {
        if ($this->expenseShares->count() === 0) {
            return false;
        }
        foreach ($this->expenseShares as $share) {
            if (!$share->isPaid()) {
                return false;
            }
        }

        return true;
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único del gasto.
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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

    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /** Normaliza el tipo de pago para evitar valores desconocidos. */
    public function setPaymentType(string $paymentType): static
    {
        $this->paymentType = in_array($paymentType, ['individual', 'shared'], true) ? $paymentType : 'shared';

        return $this;
    }

    public function getPaidBy(): ?User
    {
        return $this->paidBy;
    }

    public function setPaidBy(?User $paidBy): static
    {
        $this->paidBy = $paidBy;

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

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): static
    {
        $this->household = $household;

        return $this;
    }

    /**
     * @return Collection<int, ExpenseShare>
     */
    public function getExpenseShares(): Collection
    {
        return $this->expenseShares;
    }

    /** Añade una cuota y sincroniza la relacion inversa. */
    public function addExpenseShare(ExpenseShare $expenseShare): static
    {
        if (!$this->expenseShares->contains($expenseShare)) {
            $this->expenseShares->add($expenseShare);
            $expenseShare->setExpense($this);
        }

        return $this;
    }

    /** Quita una cuota y limpia su referencia al gasto. */
    public function removeExpenseShare(ExpenseShare $expenseShare): static
    {
        if ($this->expenseShares->removeElement($expenseShare)) {
            if ($expenseShare->getExpense() === $this) {
                $expenseShare->setExpense(null);
            }
        }

        return $this;
    }

    /** Elimina todas las cuotas antes de recalcular participantes. */
    public function removeAllExpenseShares(): void
    {
        foreach ($this->expenseShares->toArray() as $share) {
            $this->removeExpenseShare($share);
        }
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(?string $periodicity): static
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    public function getRecurrenceDayOfMonth(): ?int
    {
        return $this->recurrenceDayOfMonth;
    }

    public function setRecurrenceDayOfMonth(?int $recurrenceDayOfMonth): static
    {
        $this->recurrenceDayOfMonth = $recurrenceDayOfMonth;

        return $this;
    }

    public function getRecurrenceWeekday(): ?int
    {
        return $this->recurrenceWeekday;
    }

    public function setRecurrenceWeekday(?int $recurrenceWeekday): static
    {
        $this->recurrenceWeekday = $recurrenceWeekday;

        return $this;
    }

    public function getRecurrenceTime(): ?\DateTimeInterface
    {
        return $this->recurrenceTime;
    }

    public function setRecurrenceTime(?\DateTimeInterface $recurrenceTime): static
    {
        $this->recurrenceTime = $recurrenceTime;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
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
}
