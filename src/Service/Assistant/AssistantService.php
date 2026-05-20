<?php

namespace App\Service\Assistant;

use App\Entity\Household;
use App\Entity\User;

class AssistantService
{
    public function __construct(
        private AssistantContextBuilder $contextBuilder
    ) {
    }

    public function ask(User $user, string $message): array
    {
        $this->contextBuilder->buildForUser($user);
        $intent = $this->detectIntent($message);

        return match ($intent) {
            'tasks' => $this->answerPendingTasks($user),
            'events' => $this->answerUpcomingEvents($user),
            'payments' => $this->answerPendingPayments($user),
            'balances' => $this->answerBalances($user),
            'summary' => $this->answerSummary($user),
            default => [
                'answer' => 'Solo puedo ayudar con informacion de HomeTab: tareas, eventos, gastos compartidos, pagos pendientes y balances de tu hogar.',
                'sources' => []
            ],
        };
    }

    private function detectIntent(string $message): string
    {
        $m = $this->normalize($message);

        if ($this->containsAny($m, ['balance', 'saldo', 'reparto', 'settlement', 'liquidacion', 'compensar'])) {
            return 'balances';
        }

        if ($this->containsAny($m, ['pago', 'pagament', 'debo', 'deuda', 'gasto', 'despesa', 'pendent de pagar', 'pendiente de pagar'])) {
            return 'payments';
        }

        if ($this->containsAny($m, ['tarea', 'tasca', 'pendiente', 'pendent', 'todo'])) {
            return 'tasks';
        }

        if ($this->containsAny($m, ['evento', 'event', 'calendario', 'calendar', 'proximo', 'proper'])) {
            return 'events';
        }

        if ($this->containsAny($m, ['resumen', 'resum', 'general', 'estado', 'estat'])) {
            return 'summary';
        }

        return 'unknown';
    }

    private function answerPendingTasks(User $user): array
    {
        $tasks = [];
        foreach ($this->householdsFor($user) as $household) {
            foreach ($household->getTasks() as $task) {
                if ($task->isCompleted()) {
                    continue;
                }
                if ($task->getAssignedTo() && $task->getAssignedTo()->getId() !== $user->getId()) {
                    continue;
                }
                $tasks[] = [
                    'title' => $task->getTitle(),
                    'household' => $household->getName(),
                    'dueDate' => $task->getDueDate(),
                ];
            }
        }

        usort($tasks, static fn (array $a, array $b): int => ($a['dueDate'] ?? new \DateTime('9999-12-31')) <=> ($b['dueDate'] ?? new \DateTime('9999-12-31')));

        if ([] === $tasks) {
            return [
                'answer' => 'No tienes tareas pendientes asignadas.',
                'sources' => ['tasks']
            ];
        }

        $lines = array_map(
            fn (array $task): string => sprintf(
                '%s%s (%s)',
                $task['title'],
                $task['dueDate'] ? ' para el ' . $task['dueDate']->format('d/m/Y') : '',
                $task['household']
            ),
            array_slice($tasks, 0, 5)
        );

        return [
            'answer' => 'Tienes ' . count($tasks) . ' tarea(s) pendiente(s): ' . implode('; ', $lines) . '.',
            'sources' => ['tasks']
        ];
    }

    private function answerUpcomingEvents(User $user): array
    {
        $now = new \DateTimeImmutable('today');
        $events = [];

        foreach ($this->householdsFor($user) as $household) {
            foreach ($household->getEvents() as $event) {
                if ($event->getStartDate() < $now) {
                    continue;
                }
                if ($event->getParticipants()->count() > 0 && !$event->getParticipants()->contains($user)) {
                    continue;
                }
                $events[] = [
                    'title' => $event->getTitle(),
                    'household' => $household->getName(),
                    'startDate' => $event->getStartDate(),
                ];
            }
        }

        usort($events, static fn (array $a, array $b): int => $a['startDate'] <=> $b['startDate']);

        if ([] === $events) {
            return [
                'answer' => 'No tienes eventos proximos registrados.',
                'sources' => ['events']
            ];
        }

        $lines = array_map(
            static fn (array $event): string => sprintf('%s el %s (%s)', $event['title'], $event['startDate']->format('d/m/Y H:i'), $event['household']),
            array_slice($events, 0, 5)
        );

        return [
            'answer' => 'Tus proximos eventos son: ' . implode('; ', $lines) . '.',
            'sources' => ['events']
        ];
    }

    private function answerPendingPayments(User $user): array
    {
        $pending = [];

        foreach ($this->householdsFor($user) as $household) {
            foreach ($household->getExpenses() as $expense) {
                foreach ($expense->getExpenseShares() as $share) {
                    if ($share->getUser()?->getId() === $user->getId() && !$share->isPaid()) {
                        $pending[] = [
                            'expense' => $expense,
                            'amount' => (float) $share->getAmountOwed(),
                            'household' => $household->getName(),
                        ];
                    }
                }
            }
        }

        if ([] === $pending) {
            return [
                'answer' => 'No tienes pagos compartidos pendientes.',
                'sources' => ['payments', 'expenses']
            ];
        }

        $total = array_sum(array_column($pending, 'amount'));
        $lines = array_map(
            static fn (array $row): string => sprintf('%s: %.2f EUR (%s)', $row['expense']->getTitle(), $row['amount'], $row['household']),
            array_slice($pending, 0, 5)
        );

        return [
            'answer' => sprintf('Tienes %d pago(s) pendiente(s), por un total de %.2f EUR: %s.', count($pending), $total, implode('; ', $lines)),
            'sources' => ['payments', 'expenses']
        ];
    }

    private function answerBalances(User $user): array
    {
        $parts = [];

        foreach ($this->householdsFor($user) as $household) {
            $balance = $this->balanceForUser($household, $user);
            $parts[] = sprintf('%s: %s%.2f EUR', $household->getName(), $balance >= 0 ? '+' : '', $balance);
        }

        if ([] === $parts) {
            return [
                'answer' => 'No tienes hogares asociados, asi que no puedo calcular balances.',
                'sources' => ['balances']
            ];
        }

        return [
            'answer' => 'Tu balance actual es: ' . implode('; ', $parts) . '. Un valor positivo significa que te deben dinero; uno negativo, que debes compensar.',
            'sources' => ['balances', 'expenses']
        ];
    }

    private function answerSummary(User $user): array
    {
        $households = $this->householdsFor($user);
        $tasks = 0;
        $events = 0;
        $pendingPayments = 0;
        $balanceTotal = 0.0;

        foreach ($households as $household) {
            foreach ($household->getTasks() as $task) {
                if (!$task->isCompleted()) {
                    $tasks++;
                }
            }
            foreach ($household->getEvents() as $event) {
                if ($event->getStartDate() >= new \DateTimeImmutable('today')) {
                    $events++;
                }
            }
            foreach ($household->getExpenses() as $expense) {
                foreach ($expense->getExpenseShares() as $share) {
                    if ($share->getUser()?->getId() === $user->getId() && !$share->isPaid()) {
                        $pendingPayments++;
                    }
                }
            }
            $balanceTotal += $this->balanceForUser($household, $user);
        }

        return [
            'answer' => sprintf('Resumen HomeTab: %d hogar(es), %d tarea(s) pendiente(s), %d evento(s) proximo(s), %d pago(s) pendiente(s) y balance total %s%.2f EUR.', count($households), $tasks, $events, $pendingPayments, $balanceTotal >= 0 ? '+' : '', $balanceTotal),
            'sources' => ['tasks', 'events', 'payments', 'balances']
        ];
    }

    /**
     * @return Household[]
     */
    private function householdsFor(User $user): array
    {
        $households = [];
        foreach ($user->getHouseholdMemberships() as $membership) {
            $household = $membership->getHousehold();
            if ($household) {
                $households[$household->getId()] = $household;
            }
        }

        return array_values($households);
    }

    private function balanceForUser(Household $household, User $user): float
    {
        $balance = 0.0;
        foreach ($household->getExpenses() as $expense) {
            if ($expense->getPaidBy()?->getId() === $user->getId()) {
                $balance += (float) $expense->getAmount();
            }

            foreach ($expense->getExpenseShares() as $share) {
                if ($share->getUser()?->getId() === $user->getId()) {
                    $balance -= (float) $share->getAmountOwed();
                }
            }
        }

        return round($balance, 2);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return false === $converted ? $value : $converted;
    }
}
