<?php
namespace App\Controller\Api;

use App\Entity\Household;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para el dashboard del usuario.
 * Obtiene resúmenes estadísticos y de actividad para un hogar.
 */
#[Route('/api/households/{homeId}/dashboard')]
#[IsGranted('ROLE_USER')]
class ApiUserController extends AbstractController
{
    /**
     * Obtiene los datos del dashboard para un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param NotificationService $notificationService Servicio de notificaciones.
     * @return Response Respuesta JSON con estadísticas y línea de tiempo del dashboard.
     */
    #[Route('', name: 'api_user_dashboard', methods: ['GET'])]
    public function dashboard(int $homeId, EntityManagerInterface $em, NotificationService $notificationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // 1. Buscamos la casa
        $household = $em->getRepository(Household::class)->find($homeId);

        if (!$household) {
            return $this->json(['error' => 'La llar no existeix.'], 404);
        }

        // 2. SEGURIDAD CORREGIDA: Comprobamos si el usuario es miembro de esta casa
        $hasAccess = false;
        
        // MODO DIOS: Si es SuperAdmin, le dejamos pasar siempre
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $hasAccess = true;
        } else {
            // MODO NORMAL: Buscamos en sus membresías
            foreach ($user->getHouseholdMemberships() as $membership) {
                if ($membership->getHousehold()->getId() === $household->getId()) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (!$hasAccess) {
            return $this->json(['error' => 'No tens accés a aquesta llar.'], 403);
        }

        // 3. Calculamos las estadísticas
        $today = new \DateTimeImmutable('today');
        $limit = $today->modify('+7 days')->setTime(23, 59, 59);
        $tasks = array_values(array_filter($household->getTasks()->toArray(), static fn($t) => $t->isActive()));
        $expenses = array_values(array_filter($household->getExpenses()->toArray(), static fn($e) => $e->isActive()));
        $events = array_values(array_filter($household->getEvents()->toArray(), static fn($e) => $e->isActive()));
        $pendingTasks = array_values(array_filter($tasks, static fn($t) => !$t->isCompleted()));
        $pendingExpenses = array_values(array_filter($expenses, static fn($e) => $e->getPaymentType() === 'shared' ? !$e->isFullyPaidByAllParticipants() : !$e->isPaid()));
        $upcomingEvents = array_values(array_filter($events, static function ($event) use ($today, $limit): bool {
            if (!$event->getStartDate()) {
                return false;
            }
            $start = \DateTimeImmutable::createFromMutable($event->getStartDate());
            return $start >= $today && $start <= $limit;
        }));

        usort($upcomingEvents, static fn($a, $b) => $a->getStartDate() <=> $b->getStartDate());

        $timeline = [];
        foreach ($pendingTasks as $task) {
            if ($task->getDueDate()) {
                $timeline[] = [
                    'type' => 'task',
                    'title' => $task->getTitle(),
                    'date' => $task->getDueDate()->format(DATE_ATOM),
                    'route' => sprintf('/households/%d/tasks', $household->getId()),
                ];
            }
        }
        foreach ($pendingExpenses as $expense) {
            $date = $expense->getDueDate() ?: $expense->getPaidAt();
            if ($date) {
                $timeline[] = [
                    'type' => 'expense',
                    'title' => $expense->getTitle(),
                    'date' => $date->format(DATE_ATOM),
                    'amount' => (float) $expense->getAmount(),
                    'route' => sprintf('/households/%d/expenses', $household->getId()),
                ];
            }
        }
        foreach ($upcomingEvents as $event) {
            $timeline[] = [
                'type' => 'event',
                'title' => $event->getTitle(),
                'date' => $event->getStartDate()->format(DATE_ATOM),
                'route' => sprintf('/households/%d/calendar', $household->getId()),
            ];
        }
        usort($timeline, static fn($a, $b) => strcmp($a['date'], $b['date']));

        $notifications = $notificationService->syncForUser($user);
        $householdNotifications = array_values(array_filter($notifications, static fn($n) => $n->getHousehold()?->getId() === $household->getId()));

        $stats = [
            'totalTasks' => count($tasks),
            'completedTasks' => count(array_filter($tasks, fn($t) => $t->isCompleted())),
            'pendingTasks' => count($pendingTasks),
            'overdueTasks' => count(array_filter($pendingTasks, static fn($t) => $t->getDueDate() && \DateTimeImmutable::createFromMutable($t->getDueDate()) < $today)),
            'totalEvents' => count($events),
            'upcomingEvents' => count($upcomingEvents),
            'totalExpenses' => count($expenses),
            'unpaidExpenses' => count($pendingExpenses),
            'pendingAmount' => array_reduce($pendingExpenses, static fn(float $carry, $expense): float => $carry + (float) $expense->getAmount(), 0.0),
            'alerts' => count(array_filter($householdNotifications, static fn($n) => !$n->isRead())),
        ];

        return $this->json([
            'user' => [
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles()
            ],
            'household' => [
                'id' => $household->getId(),
                'name' => $household->getName(),
                'members' => count($household->getMembers()),
                'avatar' => $household->getAvatar(),
                'avatarIcon' => $household->getAvatarIcon(),
            ],
            'stats' => $stats,
            'timeline' => array_slice($timeline, 0, 8),
            'alerts' => array_slice(array_map(static fn($notification) => [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'priority' => $notification->getPriority(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'targetAt' => $notification->getTargetAt()?->format(DATE_ATOM),
                'route' => $notification->getRoute(),
                'read' => $notification->isRead(),
            ], $householdNotifications), 0, 5),
        ]);
    }
}
