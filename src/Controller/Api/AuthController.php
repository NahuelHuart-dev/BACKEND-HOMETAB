<?php

namespace App\Controller\Api;

use App\Entity\TwoFactorCode;
use App\Entity\User;
use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de API para la autenticación de usuarios.
 * Maneja login, tokens JWT, verificación en dos pasos (2FA) y restablecimiento de contraseñas.
 */
class AuthController extends AbstractController
{
    /**
     * Inicia el proceso de login. Verifica credenciales y decide si requiere 2FA o retorna token.
     * 
     * @param Request $request Petición HTTP con credenciales.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param UserPasswordHasherInterface $passwordHasher Servicio para verificar contraseña.
     * @param JWTTokenManagerInterface $jwtManager Gestor de tokens JWT.
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON con token JWT o desafío 2FA.
     */
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
        TwoFactorService $twoFactorService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = trim((string) ($data['username'] ?? $data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password) || !$user->isActive()) {
            return $this->json(['error' => 'Credenciales invalidas.'], 401);
        }

        if ($user->isTwoFactorEnabled()) {
            $challenge = $twoFactorService->startChallenge($user, TwoFactorCode::PURPOSE_LOGIN);

            return $this->json([
                'requiresTwoFactor' => true,
                'challengeId' => $challenge->getChallengeId(),
                'message' => 'Te hemos enviado un codigo de verificacion por correo.',
            ]);
        }

        return $this->json($this->tokenPayload($user, $jwtManager));
    }

    /**
     * Verifica el código 2FA durante el login y retorna el token si es válido.
     * 
     * @param Request $request Petición HTTP con el código 2FA.
     * @param JWTTokenManagerInterface $jwtManager Gestor de tokens JWT.
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON con el token JWT.
     */
    #[Route('/api/login/verify', name: 'api_login_verify_2fa', methods: ['POST'])]
    public function verifyLogin(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        TwoFactorService $twoFactorService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $user = $twoFactorService->verifyChallenge(
            (string) ($data['challengeId'] ?? ''),
            (string) ($data['code'] ?? ''),
            TwoFactorCode::PURPOSE_LOGIN
        );

        if (!$user || !$user->isActive()) {
            return $this->json(['error' => 'Codigo invalido o caducado.'], 400);
        }

        return $this->json($this->tokenPayload($user, $jwtManager));
    }

    /**
     * Inicia el proceso para habilitar la verificación en dos pasos (2FA).
     * 
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON con el desafío 2FA iniciado.
     */
    #[Route('/api/2fa/enable/start', name: 'api_2fa_enable_start', methods: ['POST'])]
    public function startEnable(TwoFactorService $twoFactorService): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'No autenticado.'], 401);
        }

        if ($user->isTwoFactorEnabled()) {
            return $this->json([
                'twoFactorEnabled' => true,
                'message' => 'La verificacion en dos pasos ya esta activa.',
            ]);
        }

        $challenge = $twoFactorService->startChallenge($user, TwoFactorCode::PURPOSE_ENABLE);

        return $this->json([
            'challengeId' => $challenge->getChallengeId(),
            'message' => 'Te hemos enviado un codigo para activar la verificacion en dos pasos.',
        ]);
    }

    /**
     * Verifica el código para habilitar la verificación en dos pasos (2FA) permanentemente.
     * 
     * @param Request $request Petición HTTP con el código.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON confirmando la activación.
     */
    #[Route('/api/2fa/enable/verify', name: 'api_2fa_enable_verify', methods: ['POST'])]
    public function verifyEnable(
        Request $request,
        EntityManagerInterface $entityManager,
        TwoFactorService $twoFactorService
    ): JsonResponse {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->json(['error' => 'No autenticado.'], 401);
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $verifiedUser = $twoFactorService->verifyChallenge(
            (string) ($data['challengeId'] ?? ''),
            (string) ($data['code'] ?? ''),
            TwoFactorCode::PURPOSE_ENABLE
        );

        if (!$verifiedUser || $verifiedUser->getId() !== $currentUser->getId()) {
            return $this->json(['error' => 'Codigo invalido o caducado.'], 400);
        }

        $currentUser->setTwoFactorEnabled(true);
        $entityManager->flush();

        return $this->json([
            'twoFactorEnabled' => true,
            'showTwoFactorPrompt' => false,
            'message' => 'Verificacion en dos pasos activada.',
        ]);
    }

    /**
     * Desactiva la verificación en dos pasos (2FA) para el usuario autenticado.
     * 
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @return JsonResponse Respuesta JSON confirmando la desactivación.
     */
    #[Route('/api/2fa/disable', name: 'api_2fa_disable', methods: ['POST'])]
    public function disable(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'No autenticado.'], 401);
        }

        $user->setTwoFactorEnabled(false);
        $entityManager->flush();

        return $this->json([
            'twoFactorEnabled' => false,
            'showTwoFactorPrompt' => true,
            'message' => 'Verificacion en dos pasos desactivada.',
        ]);
    }

    /**
     * Inicia el proceso de recuperación de contraseña enviando un código por correo.
     * 
     * @param Request $request Petición HTTP con el email.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON confirmando el inicio de la recuperación.
     */
    #[Route('/api/password-reset/start', name: 'api_password_reset_start', methods: ['POST'])]
    public function startPasswordReset(
        Request $request,
        EntityManagerInterface $entityManager,
        TwoFactorService $twoFactorService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = trim((string) ($data['email'] ?? ''));

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user && $user->isActive()) {
            $challenge = $twoFactorService->startChallenge($user, TwoFactorCode::PURPOSE_PASSWORD_RESET);

            return $this->json([
                'challengeId' => $challenge->getChallengeId(),
                'message' => 'Te hemos enviado un codigo para recuperar la contrasena.',
            ]);
        }

        return $this->json([
            'challengeId' => null,
            'message' => 'Si el correo existe, recibiras un codigo de recuperacion.',
        ]);
    }

    /**
     * Verifica el código de recuperación de contraseña y emite un token de reseteo.
     * 
     * @param Request $request Petición HTTP con el código.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param TwoFactorService $twoFactorService Servicio 2FA.
     * @return JsonResponse Respuesta JSON con el token de reseteo.
     */
    #[Route('/api/password-reset/verify', name: 'api_password_reset_verify', methods: ['POST'])]
    public function verifyPasswordReset(
        Request $request,
        EntityManagerInterface $entityManager,
        TwoFactorService $twoFactorService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $user = $twoFactorService->verifyChallenge(
            (string) ($data['challengeId'] ?? ''),
            (string) ($data['code'] ?? ''),
            TwoFactorCode::PURPOSE_PASSWORD_RESET
        );

        if (!$user || !$user->isActive()) {
            return $this->json(['error' => 'Codigo invalido o caducado.'], 400);
        }

        $plainToken = bin2hex(random_bytes(32));
        $resetToken = new PasswordResetToken();
        $resetToken
            ->setUser($user)
            ->setTokenHash(hash('sha256', $plainToken))
            ->setExpiresAt(new \DateTime('+15 minutes'));

        $entityManager->persist($resetToken);
        $entityManager->flush();

        return $this->json([
            'resetToken' => $plainToken,
            'message' => 'Codigo verificado. Ya puedes cambiar la contrasena.',
        ]);
    }

    /**
     * Finaliza el restablecimiento de contraseña validando el token y actualizando la contraseña.
     * 
     * @param Request $request Petición HTTP con la nueva contraseña y el token de reseteo.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param PasswordResetTokenRepository $tokenRepository Repositorio de tokens.
     * @param UserPasswordHasherInterface $passwordHasher Servicio para hashear contraseñas.
     * @return JsonResponse Respuesta JSON confirmando la actualización.
     */
    #[Route('/api/password-reset/finish', name: 'api_password_reset_finish', methods: ['POST'])]
    public function finishPasswordReset(
        Request $request,
        EntityManagerInterface $entityManager,
        PasswordResetTokenRepository $tokenRepository,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: [];
        $password = (string) ($data['password'] ?? '');
        $resetToken = $tokenRepository->findUsableToken((string) ($data['resetToken'] ?? ''));

        if (!$resetToken || !$resetToken->isUsable()) {
            return $this->json(['error' => 'Token invalido o caducado.'], 400);
        }

        if (mb_strlen($password) < 8) {
            return $this->json(['error' => 'La contrasena debe tener al menos 8 caracteres.'], 400);
        }

        $user = $resetToken->getUser();
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $resetToken->markUsed();
        $entityManager->flush();

        return $this->json(['message' => 'Contrasena actualizada correctamente.']);
    }

    private function tokenPayload(User $user, JWTTokenManagerInterface $jwtManager): array
    {
        return [
            'token' => $jwtManager->create($user),
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            'showTwoFactorPrompt' => !$user->isTwoFactorEnabled(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'avatar' => $user->getAvatar(),
                'avatarIcon' => $user->getAvatarIcon(),
                'roles' => $user->getRoles(),
            ],
        ];
    }
}
