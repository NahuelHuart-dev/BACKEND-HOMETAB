<?php

namespace App\Controller\twig;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /** Dashboard clasico del usuario normal basado en su primera casa. */
    #[Route('/dashboard', name: 'app_user_dashboard')]
    public function dashboard(): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            // Si no esta loguejat, redirigim a login
            return $this->redirectToRoute('app_login');
        }

        // Ja podem obtenir el household de forma swegura
        $household = $user->getHousehold();

        if (!$household) {
            throw $this->createNotFoundException('Aquest usuari no té cap llar assignada.');
        }

        // Estadístiques per l'usuari
        $stats = [
            'totalTasks' => count($household->getTasks()), // or use repository if you have it
            'completedTasks' => count(array_filter($household->getTasks()->toArray(), fn($t) => $t->isCompleted())),
            'totalEvents' => count($household->getEvents()),
            'totalExpenses' => count($household->getExpenses()),
            'unpaidExpenses' => count(array_filter($household->getExpenses()->toArray(), fn($e) => !$e->isPaid())),
        ];

        return $this->render('user/dashboard.html.twig', [
            'household' => $household,
            'stats' => $stats,
        ]);
    }
}
