<?php

namespace App\Entity;

use App\Repository\TwoFactorCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entidad TwoFactorCode.
 * 
 * Representa un código de verificación de dos factores (2FA) enviado al usuario.
 * Se utiliza para habilitar 2FA, iniciar sesión de forma segura o restablecer la contraseña.
 */
#[ORM\Entity(repositoryClass: TwoFactorCodeRepository::class)]
#[ORM\Index(columns: ['challenge_id'], name: 'IDX_2FA_CHALLENGE')]
#[ORM\Index(columns: ['expires_at'], name: 'IDX_2FA_EXPIRES')]
class TwoFactorCode
{
    public const PURPOSE_ENABLE = 'enable_2fa';
    public const PURPOSE_LOGIN = 'login_2fa';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $challengeId = null;

    #[ORM\Column(length: 64)]
    private ?string $codeHash = null;

    #[ORM\Column(length: 20)]
    private ?string $purpose = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column]
    private int $failedAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // --- GETTERS Y SETTERS ---

    /**
     * Obtiene el identificador único del código 2FA.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getChallengeId(): ?string
    {
        return $this->challengeId;
    }

    public function setChallengeId(string $challengeId): static
    {
        $this->challengeId = $challengeId;

        return $this;
    }

    public function getCodeHash(): ?string
    {
        return $this->codeHash;
    }

    public function setCodeHash(string $codeHash): static
    {
        $this->codeHash = $codeHash;

        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): static
    {
        $this->purpose = $purpose;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function incrementFailedAttempts(): static
    {
        ++$this->failedAttempts;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
