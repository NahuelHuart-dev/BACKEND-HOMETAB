<?php

namespace App\Entity;

use App\Repository\HouseholdRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad Household.
 * 
 * Representa una casa o grupo compartido donde conviven usuarios (miembros).
 * Agrupa tareas, gastos, eventos y mensajes relacionados con este hogar.
 */
#[ORM\Entity(repositoryClass: HouseholdRepository::class)]
class Household
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, HouseholdMember>
     */
    #[ORM\OneToMany(targetEntity: HouseholdMember::class, mappedBy: 'household', cascade: ['persist', 'remove'])]
    private Collection $members;

    /**
     * @var Collection<int, Task>
     */
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'household')]
    private Collection $tasks;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'household')]
    private Collection $events;

    /**
     * @var Collection<int, Expense>
     */
    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'household')]
    private Collection $expenses;

    /**
     * @var Collection<int, HouseholdMessage>
     */
    #[ORM\OneToMany(targetEntity: HouseholdMessage::class, mappedBy: 'household', cascade: ['persist', 'remove'])]
    private Collection $messages;

    #[ORM\Column(length: 10, unique: true, nullable: true)]
    private ?string $inviteCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $avatarIcon = 'pi-home';

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->expenses = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    /**
     * @return Collection<int, HouseholdMember */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único de la casa.
     */
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

    public function getInviteCode(): ?string
    {
        return $this->inviteCode;
    }

    public function setInviteCode(?string $inviteCode): static
    {
        $this->inviteCode = $inviteCode;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getAvatarIcon(): ?string
    {
        return $this->avatarIcon ?: 'pi-home';
    }

    public function setAvatarIcon(?string $avatarIcon): static
    {
        $allowedIcons = [
            'pi-home',
            'pi-building',
            'pi-users',
            'pi-heart',
            'pi-star',
            'pi-briefcase',
            'pi-sparkles',
            'pi-crown',
            'pi-map-marker',
            'pi-key',
        ];

        $avatarIcon = $avatarIcon ? str_replace('pi ', '', trim($avatarIcon)) : 'pi-home';
        $this->avatarIcon = in_array($avatarIcon, $allowedIcons, true) ? $avatarIcon : 'pi-home';

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setHousehold($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getHousehold() === $this) {
                $task->setHousehold(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setHousehold($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getHousehold() === $this) {
                $event->setHousehold(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(Expense $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
            $expense->setHousehold($this);
        }

        return $this;
    }

    public function removeExpense(Expense $expense): static
    {
        if ($this->expenses->removeElement($expense)) {
            if ($expense->getHousehold() === $this) {
                $expense->setHousehold(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, HouseholdMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(HouseholdMessage $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setHousehold($this);
        }

        return $this;
    }

    public function removeMessage(HouseholdMessage $message): static
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getHousehold() === $this) {
                $message->setHousehold(null);
            }
        }

        return $this;
    }

    /**
     * Membres (User) via home_members — útil per admin Twigs i estadístiques.
     *
     * @return Collection<int, User>
     */
    /** Devuelve directamente los usuarios miembros a partir de home_members. */
    public function getUsers(): Collection
    {
        $users = new ArrayCollection();
        foreach ($this->members as $m) {
            if ($m->getUser()) {
                $users->add($m->getUser());
            }
        }

        return $users;
    }
}
