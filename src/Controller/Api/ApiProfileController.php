<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\SafeImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para el perfil del usuario.
 * Permite obtener y actualizar la información del usuario actual.
 */
#[Route('/api/profile')]
#[IsGranted('ROLE_USER')]
class ApiProfileController extends AbstractController
{
    /**
     * Obtiene los datos del perfil del usuario autenticado.
     * 
     * @return Response Respuesta JSON con los datos del perfil.
     */
    #[Route('', name: 'api_profile_get', methods: ['GET'])]
    public function getProfile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'phoneNumber' => $user->getPhoneNumber(),
            'bio' => $user->getBio(),
            'avatar' => $user->getAvatar(),
            'avatarIcon' => $user->getAvatarIcon(),
            'roles' => $user->getRoles()
        ]);
    }

    /**
     * Actualiza los datos del perfil del usuario autenticado.
     * 
     * @param Request $request Petición HTTP con los datos a actualizar.
     * @param EntityManagerInterface $entityManager Manejador de entidades.
     * @param UserPasswordHasherInterface $passwordHasher Servicio para hashear contraseñas.
     * @param SafeImageStorage $imageStorage Servicio de almacenamiento de imágenes.
     * @return Response Respuesta JSON indicando el éxito y los datos actualizados.
     */
    #[Route('', name: 'api_profile_update', methods: ['POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SafeImageStorage $imageStorage
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        // Update Email
        if (isset($data['email'])) {
            $newEmail = trim((string) $data['email']);
            if ($newEmail !== $user->getEmail()) {
                $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    return $this->json(['error' => 'Ese email ya está en uso.'], 400);
                }
                $user->setEmail($newEmail);
            }
        }

        if (isset($data['firstName'])) $user->setFirstName(trim((string) $data['firstName']));
        if (isset($data['lastName'])) $user->setLastName(trim((string) $data['lastName']));
        if (array_key_exists('phoneNumber', $data)) $user->setPhoneNumber($data['phoneNumber'] ?: null);
        if (array_key_exists('bio', $data)) $user->setBio($data['bio'] ?: null);
        if (isset($data['avatarIcon']) && !$user->getAvatar()) {
            $user->setAvatarIcon($data['avatarIcon']);
        }

        // Avatar Upload
        if (!empty($data['avatarCropData'])) {
            $avatarPath = $imageStorage->storeDataUrl($data['avatarCropData'], 'avatars');
            if ($avatarPath === null) {
                return $this->json(['error' => 'No se pudo procesar la foto.'], 400);
            }
            $imageStorage->deletePublicFile($user->getAvatar(), 'avatars');
            $user->setAvatar($avatarPath);
        }

        // Remove Avatar
        if (!empty($data['removeAvatar'])) {
            $imageStorage->deletePublicFile($user->getAvatar(), 'avatars');
            $user->setAvatar(null);
            if (isset($data['avatarIcon'])) {
                $user->setAvatarIcon($data['avatarIcon']);
            }
        }

        // Password Change
        if (!empty($data['newPassword'])) {
            $currentPassword = (string) ($data['currentPassword'] ?? '');
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                return $this->json(['error' => 'La contraseña actual no es correcta.'], 400);
            }
            
            $newPassword = (string) $data['newPassword'];
            $confirmPassword = (string) ($data['confirmPassword'] ?? '');
            if ($newPassword !== $confirmPassword) {
                return $this->json(['error' => 'La nueva contraseña no coincide.'], 400);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Perfil actualizado.',
            'user' => [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail(),
                'phoneNumber' => $user->getPhoneNumber(),
                'bio' => $user->getBio(),
                'avatar' => $user->getAvatar(),
                'avatarIcon' => $user->getAvatarIcon(),
            ]
        ]);
    }
}
