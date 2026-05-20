<?php

namespace App\Controller\twig;

use App\Entity\ChatAccessLog;
use App\Entity\Household;
use App\Entity\User;
use App\Repository\ChatAccessLogRepository;
use App\Repository\HouseholdMessageRepository;
use App\Repository\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/chats')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminChatController extends AbstractController
{
    #[Route('', name: 'app_admin_chats', methods: ['GET'])]
    public function index(
        HouseholdRepository $householdRepository,
        HouseholdMessageRepository $messageRepository,
        ChatAccessLogRepository $accessLogRepository,
    ): Response {
        $rows = [];
        foreach ($householdRepository->findBy([], ['name' => 'ASC']) as $household) {
            $rows[] = [
                'household' => $household,
                'messageCount' => $messageRepository->countActiveForHousehold($household),
                'lastActivity' => $messageRepository->lastActivityForHousehold($household),
                'participants' => $messageRepository->participantsForHousehold($household),
                'lastAccess' => $accessLogRepository->findOneBy(['household' => $household], ['accessedAt' => 'DESC']),
            ];
        }

        return $this->render('admin/chats.html.twig', [
            'rows' => $rows,
        ]);
    }

    #[Route('/{id}/access', name: 'app_admin_chat_access', methods: ['POST'])]
    public function access(
        Household $household,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('admin_chat_access'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $reason = trim((string) $request->request->get('reason'));
        if (mb_strlen($reason) < 10) {
            $this->addFlash('danger', 'Indica un motivo administrativo concreto de al menos 10 caracteres.');

            return $this->redirectToRoute('app_admin_chats');
        }

        /** @var User $admin */
        $admin = $this->getUser();
        $log = (new ChatAccessLog())
            ->setAdmin($admin)
            ->setHousehold($household)
            ->setReason($reason)
            ->setIpAddress($request->getClientIp());

        $entityManager->persist($log);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_chat_show', [
            'id' => $household->getId(),
            'accessLog' => $log->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_chat_show', methods: ['GET'])]
    public function show(
        Household $household,
        Request $request,
        HouseholdMessageRepository $messageRepository,
        ChatAccessLogRepository $accessLogRepository,
    ): Response {
        /** @var User $admin */
        $admin = $this->getUser();
        $accessLogId = (int) $request->query->get('accessLog', 0);
        $accessLog = $accessLogId > 0 ? $accessLogRepository->find($accessLogId) : null;

        if (
            !$accessLog
            || $accessLog->getAdmin()?->getId() !== $admin->getId()
            || $accessLog->getHousehold()?->getId() !== $household->getId()
        ) {
            $this->addFlash('danger', 'Para ver el contenido de un chat debes registrar antes un motivo administrativo.');

            return $this->redirectToRoute('app_admin_chats');
        }

        return $this->render('admin/chat_audit_show.html.twig', [
            'household' => $household,
            'messages' => $messageRepository->findRecentForHousehold($household, 100),
            'accessLog' => $accessLog,
        ]);
    }

    #[Route('/{id}/send', name: 'app_admin_chat_send', methods: ['POST'])]
    public function send(
        Household $household,
        Request $request,
        EntityManagerInterface $entityManager,
        ChatAccessLogRepository $accessLogRepository,
    ): Response {
        /** @var User $admin */
        $admin = $this->getUser();
        $accessLogId = (int) $request->query->get('accessLog', 0);
        $accessLog = $accessLogId > 0 ? $accessLogRepository->find($accessLogId) : null;

        if (
            !$accessLog
            || $accessLog->getAdmin()?->getId() !== $admin->getId()
            || $accessLog->getHousehold()?->getId() !== $household->getId()
        ) {
            $this->addFlash('danger', 'Para enviar mensajes debes registrar antes un motivo administrativo válido.');
            return $this->redirectToRoute('app_admin_chats');
        }

        if (!$this->isCsrfTokenValid('admin_chat_send'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $content = trim((string) $request->request->get('content'));
        if ($content !== '') {
            $message = (new \App\Entity\HouseholdMessage())
                ->setHousehold($household)
                ->setSender($admin)
                ->setContent($content);
            $entityManager->persist($message);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_chat_show', [
            'id' => $household->getId(),
            'accessLog' => $accessLog->getId(),
        ]);
    }
}
