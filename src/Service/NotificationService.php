<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Expense;
use App\Entity\Household;
use App\Entity\Notification;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HouseholdAccessService $householdAccess,
    ) {
    }

    /** @return Notification[] */
    public function syncForUser(User $user): array
    {
        $currentKeys = [];
        $now = new \DateTimeImmutable('today');
        $limit = $now->modify('+7 days')->setTime(23, 59, 59);

        foreach ($this->householdAccess->getMemberHouseholds($user) as $household) {
            $currentKeys = array_merge($currentKeys, $this->syncHousehold($user, $household, $now, $limit));
        }

        $existing = $this->em->getRepository(Notification::class)->findBy(['user' => $user, 'isActive' => true]);
        foreach ($existing as $notification) {
            if (!in_array($notification->getNotificationKey(), $currentKeys, true)) {
                $notification->setIsActive(false);
                $notification->setUpdatedAt(new \DateTime());
            }
        }

        $this->em->flush();

        return $this->em->getRepository(Notification::class)->findActiveForUser($user);
    }

    /**
     * @return string[]
     */
    private function syncHousehold(User $user, Household $household, \DateTimeImmutable $today, \DateTimeImmutable $limit): array
    {
        $keys = [];

        foreach ($household->getTasks() as $task) {
            if (!$task->isActive() || $task->isCompleted() || !$task->getDueDate()) {
                continue;
            }

            $dueAt = \DateTimeImmutable::createFromMutable($task->getDueDate())->setTime(12, 0);
            if ($dueAt > $limit) {
                continue;
            }

            $status = $dueAt < $today ? 'overdue' : ($dueAt->format('Y-m-d') === $today->format('Y-m-d') ? 'today' : 'soon');
            $keys[] = $this->upsert(
                $user,
                $household,
                'task',
                $status === 'overdue' || $task->getPriority() === 'Alta' ? 'high' : ($status === 'today' ? 'medium' : 'normal'),
                sprintf('task-%d-%s-user-%d', $task->getId(), $status, $user->getId()),
                $task->getTitle() ?: 'Tarea pendiente',
                $this->taskMessage($status, $task),
                $dueAt,
                sprintf('/households/%d/tasks', $household->getId()),
                'task',
                $task->getId(),
            );
        }

        foreach ($household->getExpenses() as $expense) {
            if (!$expense->isActive() || $this->expenseIsSettled($expense)) {
                continue;
            }

            $dueAt = $this->expenseTargetDate($expense);
            if (!$dueAt || $dueAt > $limit) {
                continue;
            }

            $status = $dueAt < $today ? 'overdue' : ($dueAt->format('Y-m-d') === $today->format('Y-m-d') ? 'today' : 'soon');
            $keys[] = $this->upsert(
                $user,
                $household,
                'expense',
                $status === 'overdue' ? 'high' : ($status === 'today' ? 'medium' : 'normal'),
                sprintf('expense-%d-%s-user-%d', $expense->getId(), $status, $user->getId()),
                $expense->getTitle() ?: 'Gasto pendiente',
                $this->expenseMessage($status, $expense),
                $dueAt,
                sprintf('/households/%d/expenses', $household->getId()),
                'expense',
                $expense->getId(),
            );
        }

        foreach ($household->getEvents() as $event) {
            if (!$event->isActive() || !$event->getStartDate()) {
                continue;
            }

            $startAt = \DateTimeImmutable::createFromMutable($event->getStartDate());
            if ($startAt < $today || $startAt > $limit) {
                continue;
            }

            $status = $startAt->format('Y-m-d') === $today->format('Y-m-d') ? 'today' : ($startAt <= $today->modify('+1 day')->setTime(23, 59, 59) ? 'tomorrow' : 'soon');
            $keys[] = $this->upsert(
                $user,
                $household,
                'event',
                $status === 'today' ? 'medium' : 'normal',
                sprintf('event-%d-%s-user-%d', $event->getId(), $status, $user->getId()),
                $event->getTitle() ?: 'Evento próximo',
                $this->eventMessage($status, $event),
                $startAt,
                sprintf('/households/%d/calendar', $household->getId()),
                'event',
                $event->getId(),
            );
        }

        return $keys;
    }

    private function upsert(
        User $user,
        Household $household,
        string $type,
        string $priority,
        string $key,
        string $title,
        string $message,
        ?\DateTimeInterface $targetAt,
        string $route,
        string $targetType,
        ?int $targetId,
    ): string {
        $notification = $this->em->getRepository(Notification::class)->findOneBy(['notificationKey' => $key]);
        if (!$notification) {
            $notification = new Notification();
            $notification->setNotificationKey($key);
            $notification->setUser($user);
            $notification->setHousehold($household);
            $this->em->persist($notification);
        }

        $notification
            ->setType($type)
            ->setPriority($priority)
            ->setTitle($title)
            ->setMessage($message)
            ->setTargetAt($targetAt ? \DateTime::createFromInterface($targetAt) : null)
            ->setRoute($route)
            ->setTargetType($targetType)
            ->setTargetId($targetId)
            ->setIsActive(true)
            ->setUpdatedAt(new \DateTime());

        return $key;
    }

    private function expenseIsSettled(Expense $expense): bool
    {
        return $expense->getPaymentType() === 'shared'
            ? $expense->isFullyPaidByAllParticipants()
            : (bool) $expense->isPaid();
    }

    private function expenseTargetDate(Expense $expense): ?\DateTimeImmutable
    {
        $date = $expense->getDueDate() ?: $expense->getPaidAt();

        return $date ? \DateTimeImmutable::createFromInterface($date)->setTime(12, 0) : null;
    }

    private function taskMessage(string $status, Task $task): string
    {
        return match ($status) {
            'overdue' => 'Tarea vencida. Revisa quién la tiene asignada.',
            'today' => 'Tarea para hoy.',
            default => 'Tarea próxima en los próximos 7 días.',
        };
    }

    private function expenseMessage(string $status, Expense $expense): string
    {
        $amount = number_format((float) $expense->getAmount(), 2, ',', '.');

        return match ($status) {
            'overdue' => "Gasto vencido de {$amount} €.",
            'today' => "Gasto pendiente para hoy de {$amount} €.",
            default => "Gasto próximo de {$amount} €.",
        };
    }

    private function eventMessage(string $status, Event $event): string
    {
        return match ($status) {
            'today' => 'Evento programado para hoy.',
            'tomorrow' => 'Evento programado para mañana.',
            default => 'Evento próximo en los próximos 7 días.',
        };
    }
}
