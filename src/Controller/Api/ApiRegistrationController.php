<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de API para el registro de nuevos usuarios.
 */
class ApiRegistrationController extends AbstractController
{
    /**
     * Registra un nuevo usuario en el sistema.
     * 
     * @param Request $request Petición HTTP con los datos del nuevo usuario.
     * @param UserPasswordHasherInterface $passwordHasher Servicio para hashear contraseñas.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando el registro o un mensaje de error.
     */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        EntityManagerInterface $em
    ): Response {
        $data = json_decode($request->getContent(), true);

        // Validación básica
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'L\'email i la contrasenya són obligatoris'], 400);
        }

        // Comprobar si el correo ya existe
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json(['error' => 'Aquest email ja està registrat'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName'] ?? 'Nou');
        $user->setLastName($data['lastName'] ?? 'Usuari');
        
        // Encriptar la contraseña (MUY IMPORTANTE)
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Valores por defecto
        $user->setRoles(['ROLE_USER']);
        $user->setJoinedAt(new \DateTime());
        $user->setIsActive(true);
        $user->setTwoFactorEnabled(false);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Usuari registrat correctament. Ja pots iniciar sessió.',
            'showTwoFactorPrompt' => true,
            'user' => [
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName()
            ]
        ], 201);
    }
}
