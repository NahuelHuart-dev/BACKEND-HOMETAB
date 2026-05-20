<?php

namespace App\Controller\twig;

use App\Entity\TwoFactorCode;
use App\Entity\User;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /** Muestra el login web y los errores de autenticacion. */
    #[Route(path: '/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'backendDenied' => $request->query->getBoolean('backendDenied'),
        ]);
    }

    /** Punto de logout interceptado por el firewall de Symfony. */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/login/2fa', name: 'app_2fa_web_verify', methods: ['GET'])]
    public function twoFactorVerifyForm(): Response
    {
        return $this->render('security/two_factor.html.twig', [
            'title' => $this->translator->trans('auth.access_code_title'),
            'description' => $this->translator->trans('auth.access_code_description'),
            'error' => null,
        ]);
    }

    #[Route(path: '/login/2fa', name: 'app_2fa_web_verify_submit', methods: ['POST'])]
    public function twoFactorVerify(
        Request $request,
        TwoFactorService $twoFactorService
    ): Response {
        $challengeId = (string) $request->getSession()->get('two_factor_login_challenge', '');
        $user = $twoFactorService->verifyChallenge($challengeId, (string) $request->request->get('code'), TwoFactorCode::PURPOSE_LOGIN);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$user || !$currentUser || $user->getId() !== $currentUser->getId()) {
            return $this->render('security/two_factor.html.twig', [
                'title' => $this->translator->trans('auth.access_code_title'),
                'description' => $this->translator->trans('auth.access_code_description'),
                'error' => $this->translator->trans('auth.invalid_code'),
            ]);
        }

        $request->getSession()->set('two_factor_verified', true);
        $request->getSession()->remove('two_factor_login_challenge');

        return $this->redirectAfterTwoFactor($request);
    }

    #[Route(path: '/2fa/offer', name: 'app_2fa_web_offer', methods: ['GET', 'POST'])]
    public function twoFactorOffer(Request $request, TwoFactorService $twoFactorService): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->isTwoFactorEnabled()) {
            return $this->redirectAfterTwoFactor($request);
        }

        if ($request->isMethod('POST')) {
            if ($request->request->get('action') === 'enable') {
                $challenge = $twoFactorService->startChallenge($user, TwoFactorCode::PURPOSE_ENABLE);
                $request->getSession()->set('two_factor_enable_challenge', $challenge->getChallengeId());

                return $this->redirectToRoute('app_2fa_web_enable');
            }

            return $this->redirectAfterTwoFactor($request);
        }

        return $this->render('security/two_factor_offer.html.twig');
    }

    #[Route(path: '/2fa/enable', name: 'app_2fa_web_enable', methods: ['GET'])]
    public function twoFactorEnableForm(): Response
    {
        return $this->render('security/two_factor.html.twig', [
            'title' => $this->translator->trans('auth.enable_2fa_title'),
            'description' => $this->translator->trans('auth.enable_2fa_description'),
            'error' => null,
        ]);
    }

    #[Route(path: '/2fa/enable', name: 'app_2fa_web_enable_submit', methods: ['POST'])]
    public function twoFactorEnable(
        Request $request,
        TwoFactorService $twoFactorService,
        EntityManagerInterface $entityManager
    ): Response {
        $challengeId = (string) $request->getSession()->get('two_factor_enable_challenge', '');
        $verifiedUser = $twoFactorService->verifyChallenge($challengeId, (string) $request->request->get('code'), TwoFactorCode::PURPOSE_ENABLE);

        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$verifiedUser || !$currentUser || $verifiedUser->getId() !== $currentUser->getId()) {
            return $this->render('security/two_factor.html.twig', [
                'title' => $this->translator->trans('auth.enable_2fa_title'),
                'description' => $this->translator->trans('auth.enable_2fa_description'),
                'error' => $this->translator->trans('auth.invalid_code'),
            ]);
        }

        $currentUser->setTwoFactorEnabled(true);
        $request->getSession()->set('two_factor_verified', true);
        $request->getSession()->remove('two_factor_enable_challenge');
        $entityManager->flush();
        $this->addFlash('success', $this->translator->trans('auth.two_factor_enabled'));

        return $this->redirectAfterTwoFactor($request);
    }

    private function redirectAfterTwoFactor(Request $request): Response
    {
        $target = $request->getSession()->get('two_factor_after_login_target');
        $request->getSession()->remove('two_factor_after_login_target');
        if ($target) {
            return $this->redirect($target);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            $request->getSession()->invalidate();

            return $this->redirectToRoute('app_login');
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->redirectToRoute('app_login');
    }
}
