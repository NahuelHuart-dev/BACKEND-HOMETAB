<?php

namespace App\Entity;


use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\UserRepository;
use App\Entity\Expense;
use App\Entity\Event;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entidad User.
 * 
 * Representa a un usuario del sistema.
 * Contiene información de perfil, credenciales, configuración de seguridad (como 2FA)
 * y sus relaciones con hogares, eventos, gastos y listas multimedia.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Ja existeix un usuari amb aquest email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'L\'email no pot estar buit')]
    #[Assert\Email(message: 'L\'email {{ value }} no és vàlid')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: HouseholdMember::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $householdMemberships;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'participants')]
    private Collection $events;

    /**
     * @var Collection<int, Expense>
     */
    #[ORM\OneToMany(targetEntity: Expense::class, mappedBy: 'paidBy')]
    private Collection $expensesPaid;

    /**
     * @var Collection<int, ExpenseShare>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ExpenseShare::class, cascade: ['persist', 'remove'])]
    private Collection $expenseShares;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'El nom no pot estar buit')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'El nom ha de tenir almenys {{ limit }} caràcters'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Groups(['user:read'])]
    #[Assert\NotBlank(message: 'El cognom no pot estar buit')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'El cognom ha de tenir almenys {{ limit }} caràcters'
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[+]?[0-9\s\-()]+$/',
        message: 'El número de telèfon no és vàlid'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL de l\'avatar no és vàlida')]
    private ?string $avatar = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $avatarIcon = 'pi-user';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'La biografia no pot superar els {{ limit }} caràcters'
    )]
    private ?string $bio = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joinedAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $disabledAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $twoFactorEnabled = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $twoFactorEnabledAt = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->expensesPaid = new ArrayCollection();
        $this->householdMemberships = new ArrayCollection();
        $this->expenseShares = new ArrayCollection();
    }

    /**
     * Compat Twig: primera llar on l'usuari és membre (per pantalles antics que assumien una sola casa).
     */
    public function getHousehold(): ?Household
    {
        $m = $this->householdMemberships->first();

        return $m ? $m->getHousehold() : null;
    }

    /**
     * Retorna el nom complet
     */
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }
/**
     * @return Collection<int, HouseholdMember>
     */
    public function getHouseholdMemberships(): Collection
    {
        return $this->householdMemberships;
    }

    // Función para obtener directamente las casas
    /** Devuelve todas las casas donde el usuario tiene membresia. */
    public function getHouseholds(): array
    {
        $households = [];
        foreach ($this->householdMemberships as $membership) {
            $households[] = $membership->getHousehold();
        }
        return $households;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
            $event->addParticipant($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Expense>
     */
    public function getExpensesPaid(): Collection
    {
        return $this->expensesPaid;
    }

    public function addExpensesPaid(Expense $expensesPaid): static
    {
        if (!$this->expensesPaid->contains($expensesPaid)) {
            $this->expensesPaid->add($expensesPaid);
            $expensesPaid->setPaidBy($this);
        }

        return $this;
    }

    public function removeExpensesPaid(Expense $expensesPaid): static
    {
        if ($this->expensesPaid->removeElement($expensesPaid)) {
            // set the owning side to null (unless already changed)
            if ($expensesPaid->getPaidBy() === $this) {
                $expensesPaid->setPaidBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExpenseShare>
     */
    public function getExpenseShares(): Collection
    {
        return $this->expenseShares;
    }

    public function addExpenseShare(ExpenseShare $expenseShare): static
    {
        if (!$this->expenseShares->contains($expenseShare)) {
            $this->expenseShares->add($expenseShare);
            $expenseShare->setUser($this);
        }

        return $this;
    }

    public function removeExpenseShare(ExpenseShare $expenseShare): static
    {
        if ($this->expenseShares->removeElement($expenseShare)) {
            // set the owning side to null (unless already changed)
            if ($expenseShare->getUser() === $this) {
                $expenseShare->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /** Guarda la ruta publica de una foto de perfil subida. */
    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarIcon(): ?string
    {
        return $this->avatarIcon;
    }

    /** Guarda un icono PrimeIcons permitido como avatar alternativo. */
    public function setAvatarIcon(?string $avatarIcon): static
    {
        $allowed = ['pi-user', 'pi-home', 'pi-star', 'pi-heart', 'pi-sparkles', 'pi-briefcase', 'pi-crown', 'pi-face-smile', 'pi-bolt', 'pi-sun'];
        $this->avatarIcon = in_array($avatarIcon, $allowed, true) ? $avatarIcon : 'pi-user';

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getJoinedAt(): ?\DateTime
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTime $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        $this->disabledAt = $isActive ? null : ($this->disabledAt ?? new \DateTime());

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

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $twoFactorEnabled): static
    {
        $this->twoFactorEnabled = $twoFactorEnabled;
        $this->twoFactorEnabledAt = $twoFactorEnabled ? new \DateTime() : null;

        return $this;
    }

    public function getTwoFactorEnabledAt(): ?\DateTimeInterface
    {
        return $this->twoFactorEnabledAt;
    }
}
