<?php

namespace App\Controller\twig;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    /** Muestra y guarda el perfil del usuario autenticado. */
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('profile'.$user->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $newEmail = trim((string) $request->request->get('email'));
            $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Ese email ya está en uso.');

                return $this->redirectToRoute('app_profile');
            }

            $user
                ->setFirstName(trim((string) $request->request->get('firstName')))
                ->setLastName(trim((string) $request->request->get('lastName')))
                ->setEmail($newEmail)
                ->setPhoneNumber($request->request->get('phoneNumber') ?: null)
                ->setBio($request->request->get('bio') ?: null);

            if (!$user->getAvatar()) {
                $user->setAvatarIcon($request->request->get('avatarIcon') ?: 'pi-user');
            }

            $avatarCropData = (string) $request->request->get('avatarCropData');
            if ($avatarCropData !== '') {
                $avatarPath = $this->storeAvatarCrop($avatarCropData);
                if ($avatarPath === null) {
                    $this->addFlash('danger', 'No se pudo procesar el recorte de la foto.');

                    return $this->redirectToRoute('app_profile');
                }

                $this->deleteAvatarFile($user->getAvatar());
                $user->setAvatar($avatarPath);
            } else {
                $avatarFile = $request->files->get('avatarFile');
                if ($avatarFile instanceof UploadedFile) {
                    $avatarPath = $this->storeAvatarUpload($avatarFile);
                    if ($avatarPath === null) {
                        $this->addFlash('danger', 'La foto debe ser una imagen valida de maximo 2 MB.');

                        return $this->redirectToRoute('app_profile');
                    }

                    $this->deleteAvatarFile($user->getAvatar());
                    $user->setAvatar($avatarPath);
                }
            }

            if ($request->request->getBoolean('removeAvatar')) {
                $this->deleteAvatarFile($user->getAvatar());
                $user->setAvatar(null);
            }

            $newPassword = (string) $request->request->get('newPassword');
            if ($newPassword !== '') {
                $currentPassword = (string) $request->request->get('currentPassword');
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('danger', 'La contraseña actual no es correcta.');

                    return $this->redirectToRoute('app_profile');
                }

                if ($newPassword !== (string) $request->request->get('confirmPassword')) {
                    $this->addFlash('danger', 'La nueva contraseña no coincide.');

                    return $this->redirectToRoute('app_profile');
                }

                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Perfil actualizado.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'avatarIcons' => ['pi-user', 'pi-home', 'pi-star', 'pi-heart', 'pi-sparkles', 'pi-briefcase', 'pi-crown', 'pi-face-smile', 'pi-bolt', 'pi-sun'],
        ]);
    }

    /** Guarda una foto subida validandola como imagen real. */
    private function storeAvatarUpload(UploadedFile $file): ?string
    {
        if ($file->getSize() > 2 * 1024 * 1024 || !@getimagesize($file->getPathname())) {
            return null;
        }

        $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $extension = 'jpg';
        }

        $filename = bin2hex(random_bytes(8)).'.'.$extension;
        $directory = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $file->move($directory, $filename);

        return '/uploads/avatars/'.$filename;
    }

    /** Guarda el recorte cuadrado generado en el navegador. */
    private function storeAvatarCrop(string $dataUrl): ?string
    {
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            return null;
        }

        $bytes = base64_decode($matches[1], true);
        if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024 || !@getimagesizefromstring($bytes)) {
            return null;
        }

        $directory = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(8)).'.png';
        file_put_contents($directory.'/'.$filename, $bytes);

        return '/uploads/avatars/'.$filename;
    }

    /** Borra una foto de perfil guardada localmente dentro de public/uploads/avatars. */
    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (!$avatarPath || !str_starts_with($avatarPath, '/uploads/avatars/')) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir').'/public'.$avatarPath;
        $realPath = realpath($fullPath);
        $avatarDir = realpath($this->getParameter('kernel.project_dir').'/public/uploads/avatars');
        if ($realPath && $avatarDir && str_starts_with($realPath, $avatarDir) && is_file($realPath)) {
            unlink($realPath);
        }
    }
}
