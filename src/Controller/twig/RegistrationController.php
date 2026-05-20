<?php

namespace App\Controller\twig;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    /** El backend no permite altas web: solo accede la cuenta superadmin. */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(TranslatorInterface $translator): Response
    {
        $this->addFlash('info', $translator->trans('registration.disabled'));

        return $this->redirectToRoute('app_login');
    }
}
