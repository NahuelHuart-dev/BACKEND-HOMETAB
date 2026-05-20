<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para notificaciones.
 * Permite listar y marcar notificaciones como leídas.
 */
#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class ApiNotificationController extends AbstractController
{
    /**
     * Obtiene y sincroniza las notificaciones del usuario, agrupadas por hogar.
     * 
     * @param NotificationService $notificationService Servicio de notificaciones.
     * @return Response Respuesta JSON con las notificaciones agrupadas.
     */
    #[Route('', name: 'api_notifications_index', methods: ['GET'])]
    public function index(NotificationService $notificationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $notifications = $notificationService->syncForUser($user);

        return $this->json($this->mapGrouped($notifications));
    }

    /**
     * Marca una notificación específica como leída.
     * 
     * @param int $id ID de la notificación.
     * @param NotificationRepository $repository Repositorio de notificaciones.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando la acción.
     */
    #[Route('/{id}/read', name: 'api_notifications_read', methods: ['POST'])]
    public function markRead(int $id, NotificationRepository $repository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $notification = $repository->find($id);
        if (!$notification || $notification->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Notificació no trobada.'], 404);
        }

        $notification->setIsRead(true);
        $notification->setUpdatedAt(new \DateTime());
        $em->flush();

        return $this->json(['message' => 'Notificació llegida.']);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     * 
     * @param NotificationRepository $repository Repositorio de notificaciones.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando la acción.
     */
    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['POST'])]
    public function markAllRead(NotificationRepository $repository, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        foreach ($repository->findActiveForUser($user) as $notification) {
            $notification->setIsRead(true);
            $notification->setUpdatedAt(new \DateTime());
        }
        $em->flush();

        return $this->json(['message' => 'Notificacions llegides.']);
    }

    /** @param Notification[] $notifications */
    private function mapGrouped(array $notifications): array
    {
        $groups = [];
        $totalUnread = 0;

        foreach ($notifications as $notification) {
            $household = $notification->getHousehold();
            if (!$household) {
                continue;
            }

            $householdId = (int) $household->getId();
            if (!isset($groups[$householdId])) {
                $groups[$householdId] = [
                    'id' => $householdId,
                    'name' => $household->getName(),
                    'avatar' => $household->getAvatar(),
                    'avatarIcon' => $household->getAvatarIcon(),
                    'unreadCount' => 0,
                    'items' => [],
                ];
            }

            if (!$notification->isRead()) {
                $groups[$householdId]['unreadCount']++;
                $totalUnread++;
            }

            $groups[$householdId]['items'][] = [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'priority' => $notification->getPriority(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'targetAt' => $notification->getTargetAt()?->format(DATE_ATOM),
                'route' => $notification->getRoute(),
                'targetType' => $notification->getTargetType(),
                'targetId' => $notification->getTargetId(),
                'read' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return [
            'totalUnread' => $totalUnread,
            'households' => array_values($groups),
        ];
    }
}
