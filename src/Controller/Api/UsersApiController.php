<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de API para listar usuarios.
 * Lista de usuarios: mismo recurso en JSON y en vista Twig (depuración / admin ligero).
 */
#[Route('/api')]
class UsersApiController extends AbstractController
{
    /**
     * Obtiene y devuelve la lista de todos los usuarios en formato JSON.
     * 
     * @param UserRepository $userRepository Repositorio de usuarios.
     * @return Response Respuesta JSON con la lista de usuarios.
     */
    #[Route('/users', name: 'api_users_list', methods: ['GET'])]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->json($users, 200, [], ['groups' => 'user:read']);
    }

    /**
     * Devuelve una vista Twig con la lista de todos los usuarios.
     * 
     * @param UserRepository $userRepository Repositorio de usuarios.
     * @return Response Respuesta HTTP con la vista renderizada.
     */
    #[Route('/users/view', name: 'api_users_list_view', methods: ['GET'])]
    public function listUsersView(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('api/users_list.html.twig', [
            'users' => $users,
        ]);
    }
}
