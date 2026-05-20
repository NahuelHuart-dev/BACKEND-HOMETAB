<?php

namespace App\Controller\Api;

use App\Entity\Expense;
use App\Entity\ExpenseShare;
use App\Entity\Household;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para la gestión de gastos en un hogar.
 * Proporciona endpoints para listar, crear, editar, eliminar y consultar saldos y liquidaciones de gastos.
 */
#[Route('/api/households/{homeId}/expenses')]
#[IsGranted('ROLE_USER')]
class ApiExpenseController extends AbstractController
{
    private function getValidHousehold(int $homeId, User $user, EntityManagerInterface $em): ?Household
    {
        $household = $em->getRepository(Household::class)->find($homeId);
        if (!$household) {
            return null;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $household;
        }

        foreach ($user->getHouseholdMemberships() as $membership) {
            if ($membership->getHousehold()->getId() === $household->getId()) {
                return $household;
            }
        }

        return null;
    }

    private function userBelongsToHousehold(User $u, Household $household): bool
    {
        foreach ($u->getHouseholdMemberships() as $membership) {
            if ($membership->getHousehold()->getId() === $household->getId()) {
                return true;
            }
        }

        return false;
    }

    private function mapExpenseToArray(Expense $expense): array
    {
        $shares = [];
        foreach ($expense->getExpenseShares() as $s) {
            $u = $s->getUser();
            $shares[] = [
                'userId' => $u?->getId(),
                'name' => $u?->getFullName(),
                'email' => $u?->getEmail(),
                'amountOwed' => $s->getAmountOwed(),
                'isPaid' => $s->isPaid(),
                'paidAt' => $s->getPaidAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'id' => $expense->getId(),
            'title' => $expense->getTitle(),
            'description' => $expense->getDescription(),
            'amount' => $expense->getAmount(),
            'category' => $expense->getCategory(),
            'paymentType' => $expense->getPaymentType(),
            'isPaid' => $expense->isPaid(),
            'dueDate' => $expense->getDueDate() ? $expense->getDueDate()->format('Y-m-d') : null,
            'periodicity' => $expense->getPeriodicity(),
            'recurrenceDayOfMonth' => $expense->getRecurrenceDayOfMonth(),
            'recurrenceWeekday' => $expense->getRecurrenceWeekday(),
            'recurrenceTime' => $expense->getRecurrenceTime()?->format('H:i'),
            'paidAt' => $expense->getPaidAt() ? $expense->getPaidAt()->format('Y-m-d H:i:s') : null,

            'paidBy' => $expense->getPaidBy() ? [
                'id' => $expense->getPaidBy()->getId(),
                'name' => $expense->getPaidBy()->getFullName(),
                'email' => $expense->getPaidBy()->getEmail(),
            ] : null,

            'shares' => $shares,
            'splitBetween' => array_map(
                static fn (array $row) => [
                    'id' => $row['userId'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                ],
                $shares
            ),
            'amountPerPerson' => $expense->resolveAverageShareAmount(),
            'allParticipantsPaid' => $expense->isFullyPaidByAllParticipants(),
        ];
    }

    /**
     * Obtiene la lista de gastos activos para un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON con la lista de gastos o un mensaje de error.
     */
    #[Route('', name: 'api_expense_index', methods: ['GET'])]
    public function index(int $homeId, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $this->getValidHousehold($homeId, $user, $em);

        if (!$household) {
            return $this->json(['error' => 'Accés denegat'], 403);
        }

        $expenses = $em->getRepository(Expense::class)->findBy(['household' => $household, 'isActive' => true]);
        $data = array_map([$this, 'mapExpenseToArray'], $expenses);

        return $this->json($data, 200);
    }

    /**
     * Crea un nuevo gasto en un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con los datos del gasto.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON indicando el éxito y los datos del gasto creado, o un error.
     */
    #[Route('', name: 'api_expense_new', methods: ['POST'])]
    public function new(int $homeId, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $data = json_decode($request->getContent(), true);

            if (empty($data['title']) || !isset($data['amount'])) {
                return $this->json(['error' => 'Títol i quantitat són obligatoris'], 400);
            }

            $expense = new Expense();
            $paymentType = $data['paymentType'] ?? 'shared';
            $expense->setTitle($data['title']);
            $expense->setDescription($data['description'] ?? '');
            $expense->setAmount((string) $data['amount']);
            $expense->setCategory($data['category'] ?? 'General');
            $expense->setPaymentType($paymentType);
            $expense->setHousehold($household);

            $payer = null;
            if ($paymentType === 'individual') {
                if (empty($data['paidByUserId'])) {
                    return $this->json(['error' => 'El pagador es obligatorio en pagos individuales'], 400);
                }
                $candidate = $em->getRepository(User::class)->find((int) $data['paidByUserId']);
                if (!$candidate || !$this->userBelongsToHousehold($candidate, $household)) {
                    return $this->json(['error' => 'El pagador no és membre vàlid d\'aquesta llar'], 400);
                }
                $payer = $candidate;
            }

            $expense->setIsPaid(isset($data['isPaid']) ? (bool) $data['isPaid'] : false);

            if (!empty($data['dueDate'])) {
                $expense->setDueDate(new \DateTime($data['dueDate']));
            }

            $periodicity = $data['periodicity'] ?? null;
            $expense
                ->setPeriodicity($periodicity ?: null)
                ->setPaidAt(!empty($data['paidAt']) ? new \DateTime($data['paidAt']) : null)
                ->setRecurrenceDayOfMonth($periodicity === 'monthly' ? (int) ($data['recurrenceDayOfMonth'] ?? 0) : null)
                ->setRecurrenceWeekday($periodicity === 'weekly' ? (int) ($data['recurrenceWeekday'] ?? 0) : null)
                ->setRecurrenceTime($periodicity === 'daily' && !empty($data['recurrenceTime']) ? new \DateTime($data['recurrenceTime']) : null);

            $splitUserIds = [];
            if ($paymentType === 'individual') {
                $splitUserIds = [(int) $payer->getId()];
            } elseif (!empty($data['splitBetween']) && is_array($data['splitBetween'])) {
                $splitUserIds = array_map('intval', $data['splitBetween']);
            } else {
                return $this->json(['error' => 'Selecciona participantes para el pago conjunto'], 400);
            }

            $splitUsers = [];
            foreach ($splitUserIds as $userId) {
                $splitUser = $em->getRepository(User::class)->find($userId);
                if (!$splitUser) {
                    return $this->json(['error' => 'Un dels usuaris seleccionats no existeix'], 400);
                }
                if (!$this->userBelongsToHousehold($splitUser, $household)) {
                    return $this->json(['error' => 'Un dels usuaris seleccionats no pertany a aquesta llar'], 400);
                }
                $splitUsers[] = $splitUser;
            }
            if ($paymentType !== 'individual') {
                $payer = $splitUsers[0] ?? $user;
            }
            $expense->setPaidBy($payer);

            $count = count($splitUsers);
            $per = $count > 0 ? $expense->getAmountPerPerson($count) : 0.0;
            $paidShareIds = [];
            if (!empty($data['sharePayments']) && is_array($data['sharePayments'])) {
                foreach ($data['sharePayments'] as $row) {
                    if (!empty($row['isPaid'])) {
                        $paidShareIds[] = (int) ($row['userId'] ?? 0);
                    }
                }
            }
            foreach ($splitUsers as $splitUser) {
                $share = new ExpenseShare();
                $share->setUser($splitUser);
                $share->setAmountOwed((string) round($per, 2));
                $sharePaid = $paymentType === 'individual' ? $expense->isPaid() : in_array($splitUser->getId(), $paidShareIds, true);
                $share->setIsPaid($sharePaid);
                $share->setPaidAt($sharePaid ? new \DateTime() : null);
                $expense->addExpenseShare($share);
            }

            $em->persist($expense);
            $em->flush();

            return $this->json([
                'message' => 'Despesa afegida correctament',
                'expense' => $this->mapExpenseToArray($expense),
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edita un gasto existente en un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del gasto.
     * @param Request $request Petición HTTP con los datos a actualizar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON indicando el éxito y los datos actualizados, o un error.
     */
    #[Route('/{id}', name: 'api_expense_edit', methods: ['PUT', 'PATCH'])]
    public function edit(int $homeId, int $id, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expense = $em->getRepository(Expense::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$expense) {
                return $this->json(['error' => 'Despesa no trobada'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) {
                $expense->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $expense->setDescription($data['description']);
            }
            if (isset($data['amount'])) {
                $expense->setAmount((string) $data['amount']);
            }
            if (isset($data['category'])) {
                $expense->setCategory($data['category']);
            }
            if (isset($data['paymentType'])) {
                $expense->setPaymentType($data['paymentType']);
            }
            $paymentType = $expense->getPaymentType();

            if (isset($data['isPaid'])) {
                $expense->setIsPaid((bool) $data['isPaid']);
            }

            if (array_key_exists('dueDate', $data)) {
                $expense->setDueDate(empty($data['dueDate']) ? null : new \DateTime($data['dueDate']));
            }

            if (isset($data['periodicity'])) {
                $periodicity = $data['periodicity'] ?: null;
                $expense
                    ->setPeriodicity($periodicity)
                    ->setRecurrenceDayOfMonth($periodicity === 'monthly' ? (int) ($data['recurrenceDayOfMonth'] ?? 0) : null)
                    ->setRecurrenceWeekday($periodicity === 'weekly' ? (int) ($data['recurrenceWeekday'] ?? 0) : null)
                    ->setRecurrenceTime($periodicity === 'daily' && !empty($data['recurrenceTime']) ? new \DateTime($data['recurrenceTime']) : null);
            }

            if (array_key_exists('paidAt', $data)) {
                $expense->setPaidAt(empty($data['paidAt']) ? null : new \DateTime($data['paidAt']));
            }

            if ($paymentType === 'individual' && !empty($data['paidByUserId'])) {
                $candidate = $em->getRepository(User::class)->find((int) $data['paidByUserId']);
                if (!$candidate || !$this->userBelongsToHousehold($candidate, $household)) {
                    return $this->json(['error' => 'El pagador no és membre vàlid d\'aquesta llar'], 400);
                }
                $expense->setPaidBy($candidate);
            }

            if ($paymentType === 'individual') {
                $expense->removeAllExpenseShares();
                $payer = $expense->getPaidBy();
                if (!$payer) {
                    return $this->json(['error' => 'El pagador es obligatorio en pagos individuales'], 400);
                }
                $share = new ExpenseShare();
                $share->setUser($payer);
                $share->setAmountOwed((string) $expense->getAmount());
                $share->setIsPaid((bool) $expense->isPaid());
                $share->setPaidAt($expense->isPaid() ? new \DateTime() : null);
                $expense->addExpenseShare($share);
            } elseif (!empty($data['splitBetween']) && is_array($data['splitBetween'])) {
                $expense->removeAllExpenseShares();
                $splitUserIds = array_map('intval', $data['splitBetween']);
                $splitUsers = [];
                foreach ($splitUserIds as $userId) {
                    $splitUser = $em->getRepository(User::class)->find($userId);
                    if (!$splitUser || !$this->userBelongsToHousehold($splitUser, $household)) {
                        return $this->json(['error' => 'splitBetween invàlid'], 400);
                    }
                    $splitUsers[] = $splitUser;
                }
                if (count($splitUsers) === 0) {
                    return $this->json(['error' => 'Selecciona participantes para el pago conjunto'], 400);
                }
                $expense->setPaidBy($splitUsers[0]);
                $count = count($splitUsers);
                $per = $count > 0 ? $expense->getAmountPerPerson($count) : 0.0;
                $paidShareIds = [];
                if (!empty($data['sharePayments']) && is_array($data['sharePayments'])) {
                    foreach ($data['sharePayments'] as $row) {
                        if (!empty($row['isPaid'])) {
                            $paidShareIds[] = (int) ($row['userId'] ?? 0);
                        }
                    }
                }
                foreach ($splitUsers as $splitUser) {
                    $share = new ExpenseShare();
                    $share->setUser($splitUser);
                    $share->setAmountOwed((string) round($per, 2));
                    $sharePaid = in_array($splitUser->getId(), $paidShareIds, true);
                    $share->setIsPaid($sharePaid);
                    $share->setPaidAt($sharePaid ? new \DateTime() : null);
                    $expense->addExpenseShare($share);
                }
            }

            if (!empty($data['sharePayments']) && is_array($data['sharePayments'])) {
                foreach ($data['sharePayments'] as $row) {
                    $uid = (int) ($row['userId'] ?? 0);
                    $paid = (bool) ($row['isPaid'] ?? false);
                    foreach ($expense->getExpenseShares() as $share) {
                        if ($share->getUser()->getId() === $uid) {
                            $share->setIsPaid($paid);
                            $share->setPaidAt($paid ? new \DateTime() : null);
                        }
                    }
                }
            }

            $em->flush();

            return $this->json([
                'message' => 'Despesa actualitzada',
                'expense' => $this->mapExpenseToArray($expense),
            ], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina (da de baja lógica) un gasto.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del gasto a eliminar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON confirmando la eliminación o un mensaje de error.
     */
    #[Route('/{id}', name: 'api_expense_delete', methods: ['DELETE'])]
    public function delete(int $homeId, int $id, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expense = $em->getRepository(Expense::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$expense) {
                return $this->json(['error' => 'Despesa no trobada'], 404);
            }

            $expense->setIsActive(false);
            $em->flush();

            return $this->json(['message' => 'Despesa donada de baixa']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Historial de gastos archivados (soft-deleted) de la casa.
     * GET /api/households/{homeId}/expenses/history
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON con la lista de gastos archivados.
     */
    #[Route('/history', name: 'api_expense_history', methods: ['GET'])]
    public function history(int $homeId, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expenses = $em->getRepository(Expense::class)->findBy(
                ['household' => $household, 'isActive' => false],
                ['disabledAt' => 'DESC']
            );

            $data = array_map(function (Expense $expense): array {
                $base = $this->mapExpenseToArray($expense);
                $base['disabledAt'] = $expense->getDisabledAt()?->format('Y-m-d H:i:s');
                return $base;
            }, $expenses);

            return $this->json($data, 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restaurar un gasto archivado (volver a isActive = true).
     * PATCH /api/households/{homeId}/expenses/{id}/restore
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del gasto.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON indicando el éxito de la restauración.
     */
    #[Route('/{id}/restore', name: 'api_expense_restore', methods: ['PATCH'])]
    public function restore(int $homeId, int $id, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expense = $em->getRepository(Expense::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$expense) {
                return $this->json(['error' => 'Despesa no trobada'], 404);
            }

            if ($expense->isActive()) {
                return $this->json(['error' => 'La despesa ja és activa'], 409);
            }

            $expense->setIsActive(true);
            $em->flush();

            return $this->json([
                'message' => 'Despesa restaurada correctament',
                'expense' => $this->mapExpenseToArray($expense),
            ], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calcula y devuelve el balance de gastos por usuario en un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON con los balances por usuario.
     */
    #[Route('/balance', name: 'api_expense_balance', methods: ['GET'])]
    public function balance(int $homeId, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expenses = $em->getRepository(Expense::class)->findBy([
                'household' => $household,
                'isActive' => true,
            ]);

            $balances = [];

            foreach ($household->getMembers() as $householdMembership) {
                $memberUser = $householdMembership->getUser();
                $balances[$memberUser->getId()] = [
                    'userId' => $memberUser->getId(),
                    'name' => $memberUser->getFullName(),
                    'email' => $memberUser->getEmail(),
                    'balance' => 0,
                ];
            }

            foreach ($expenses as $expense) {
                if ($expense->getExpenseShares()->count() === 0) {
                    continue;
                }

                $paidBy = $expense->getPaidBy();

                if ($paidBy && isset($balances[$paidBy->getId()])) {
                    $balances[$paidBy->getId()]['balance'] += (float) $expense->getAmount();
                }

                foreach ($expense->getExpenseShares() as $share) {
                    $u = $share->getUser();
                    if (!$u || !isset($balances[$u->getId()])) {
                        continue;
                    }
                    $balances[$u->getId()]['balance'] -= (float) $share->getAmountOwed();
                }
            }

            return $this->json(array_values($balances), 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error Backend: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcula y devuelve las liquidaciones (settlements) de deudas entre los miembros del hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON con las transacciones a realizar para liquidar las deudas.
     */
    #[Route('/settlements', name: 'api_expense_settlements', methods: ['GET'])]
    public function settlements(int $homeId, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) {
                return $this->json(['error' => 'Accés denegat'], 403);
            }

            $expenses = $em->getRepository(Expense::class)->findBy([
                'household' => $household,
                'isActive' => true,
            ]);

            $balances = [];

            foreach ($household->getMembers() as $membership) {
                $memberUser = $membership->getUser();

                $balances[$memberUser->getId()] = [
                    'user' => $memberUser,
                    'balance' => 0,
                ];
            }

            foreach ($expenses as $expense) {
                if ($expense->getExpenseShares()->count() === 0) {
                    continue;
                }

                $paidBy = $expense->getPaidBy();

                if ($paidBy && isset($balances[$paidBy->getId()])) {
                    $balances[$paidBy->getId()]['balance'] += (float) $expense->getAmount();
                }

                foreach ($expense->getExpenseShares() as $share) {
                    $u = $share->getUser();
                    if (!$u || !isset($balances[$u->getId()])) {
                        continue;
                    }
                    $balances[$u->getId()]['balance'] -= (float) $share->getAmountOwed();
                }
            }

            $debtors = [];
            $creditors = [];

            foreach ($balances as $balanceData) {
                $memberUser = $balanceData['user'];
                $balance = round($balanceData['balance'], 2);

                if ($balance < 0) {
                    $debtors[] = [
                        'user' => $memberUser,
                        'amount' => abs($balance),
                    ];
                }

                if ($balance > 0) {
                    $creditors[] = [
                        'user' => $memberUser,
                        'amount' => $balance,
                    ];
                }
            }

            $settlements = [];

            $debtorIndex = 0;
            $creditorIndex = 0;

            while ($debtorIndex < count($debtors) && $creditorIndex < count($creditors)) {
                $debtor = &$debtors[$debtorIndex];
                $creditor = &$creditors[$creditorIndex];

                $amountToPay = min($debtor['amount'], $creditor['amount']);
                $amountToPay = round($amountToPay, 2);

                if ($amountToPay > 0) {
                    $settlements[] = [
                        'from' => [
                            'id' => $debtor['user']->getId(),
                            'name' => $debtor['user']->getFullName(),
                            'email' => $debtor['user']->getEmail(),
                        ],
                        'to' => [
                            'id' => $creditor['user']->getId(),
                            'name' => $creditor['user']->getFullName(),
                            'email' => $creditor['user']->getEmail(),
                        ],
                        'amount' => $amountToPay,
                    ];
                }

                $debtor['amount'] -= $amountToPay;
                $creditor['amount'] -= $amountToPay;

                if (round($debtor['amount'], 2) <= 0) {
                    $debtorIndex++;
                }

                if (round($creditor['amount'], 2) <= 0) {
                    $creditorIndex++;
                }
            }

            return $this->json($settlements, 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error Backend: ' . $e->getMessage(),
            ], 500);
        }
    }
}
