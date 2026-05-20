<?php

namespace App\Controller\twig;

use App\Entity\Event;
use App\Entity\Expense;
use App\Entity\ExpenseShare;
use App\Entity\User;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Task;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\HouseholdRepository;
use App\Repository\TaskRepository;
use App\Repository\EventRepository;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class AdminController extends AbstractController
{
    /** Muestra todas las casas disponibles para el superadmin. */
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        HouseholdRepository $householdRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'households' => $householdRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    /** Vista global para administrar casas desde el desplegable de administracion. */
    #[Route('/households', name: 'app_admin_households', methods: ['GET'])]
    public function households(
        Request $request,
        HouseholdRepository $householdRepository
    ): Response {
        $qb = $householdRepository->createQueryBuilder('h')
            ->leftJoin('h.members', 'm')->addSelect('m')
            ->leftJoin('m.user', 'u')->addSelect('u')
            ->orderBy('h.id', 'ASC');

        if ($request->query->get('q')) {
            $qb->andWhere('LOWER(h.name) LIKE :q OR LOWER(h.inviteCode) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower((string) $request->query->get('q')).'%');
        }

        $rows = $qb->getQuery()->getResult();
        $households = [];
        foreach ($rows as $household) {
            $householdId = $household->getId();
            $households[$householdId] = [
                'entity' => $household,
                'memberCount' => $household->getMembers()->count(),
                'taskCount' => $household->getTasks()->count(),
                'eventCount' => $household->getEvents()->count(),
                'expenseCount' => $household->getExpenses()->count(),
                'messageCount' => $household->getMessages()->count(),
            ];
        }

        return $this->render('admin/households.html.twig', [
            'households' => array_values($households),
            'filters' => $request->query->all(),
            'avatarIcons' => $this->householdAvatarIcons(),
        ]);
    }

    /** Crea una casa nueva y asigna al superadmin como propietario. */
    #[Route('/households/create', name: 'app_admin_household_create', methods: ['POST'])]
    public function createHousehold(
        Request $request,
        EntityManagerInterface $entityManager,
        HouseholdRepository $householdRepository
    ): Response {
        if (!$this->isCsrfTokenValid('admin_household_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $redirectRoute = $request->request->get('_redirect') === 'households' ? 'app_admin_households' : 'app_admin_dashboard';
        $name = trim((string) $request->request->get('name'));
        $inviteCode = strtoupper(trim((string) $request->request->get('inviteCode')));
        if ($name === '') {
            $this->addFlash('danger', 'La casa necesita un nombre.');

            return $this->redirectToRoute($redirectRoute);
        }

        if ($inviteCode === '') {
            $inviteCode = $this->generateInviteCode($householdRepository);
        }

        if (!preg_match('/^[A-Z0-9]{4,10}$/', $inviteCode)) {
            $this->addFlash('danger', 'El codigo debe tener entre 4 y 10 caracteres alfanumericos.');

            return $this->redirectToRoute($redirectRoute);
        }

        if ($householdRepository->findOneBy(['inviteCode' => $inviteCode])) {
            $this->addFlash('danger', 'Ese codigo ya esta en uso.');

            return $this->redirectToRoute($redirectRoute);
        }

        /** @var User $admin */
        $admin = $this->getUser();
        $household = (new Household())
            ->setName($name)
            ->setInviteCode($inviteCode)
            ->setAvatarIcon((string) $request->request->get('avatarIcon', 'pi-home'));

        if (!$this->applyHouseholdAvatar($household, $request)) {
            return $this->redirectToRoute($redirectRoute);
        }

        $membership = (new HouseholdMember())
            ->setHousehold($household)
            ->setUser($admin)
            ->setRole('owner');

        $entityManager->persist($household);
        $entityManager->persist($membership);
        $entityManager->flush();
        $this->addFlash('success', 'Casa creada.');

        if ($request->request->get('_redirect') === 'households') {
            return $this->redirectToRoute('app_admin_households');
        }

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId()]);
    }

    /** Actualiza nombre, icono y codigo de invitacion de una casa. */
    #[Route('/households/{id}/save', name: 'app_admin_household_save', methods: ['POST'])]
    public function saveHousehold(
        Household $household,
        Request $request,
        EntityManagerInterface $entityManager,
        HouseholdRepository $householdRepository
    ): Response {
        if (!$this->isCsrfTokenValid('admin_household'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $name = trim((string) $request->request->get('name'));
        $inviteCode = strtoupper(trim((string) $request->request->get('inviteCode')));
        if ($name === '') {
            $this->addFlash('danger', 'La casa necesita un nombre.');

            return $this->redirectToRoute('app_admin_households');
        }

        if ($inviteCode === '') {
            $inviteCode = $this->generateInviteCode($householdRepository);
        }

        if (!preg_match('/^[A-Z0-9]{4,10}$/', $inviteCode)) {
            $this->addFlash('danger', 'El codigo debe tener entre 4 y 10 caracteres alfanumericos.');

            return $this->redirectToRoute('app_admin_households');
        }

        $existing = $householdRepository->findOneBy(['inviteCode' => $inviteCode]);
        if ($existing && $existing->getId() !== $household->getId()) {
            $this->addFlash('danger', 'Ese codigo ya esta en uso por otra casa.');

            return $this->redirectToRoute('app_admin_households');
        }

        $household
            ->setName($name)
            ->setInviteCode($inviteCode)
            ->setAvatarIcon((string) $request->request->get('avatarIcon', 'pi-home'));

        if (!$this->applyHouseholdAvatar($household, $request)) {
            return $this->redirectToRoute('app_admin_households');
        }

        $entityManager->flush();
        $this->addFlash('success', 'Casa actualizada.');

        return $this->redirectToRoute('app_admin_households');
    }

    /** Elimina casas vacias para evitar romper datos vinculados. */
    #[Route('/households/{id}/delete', name: 'app_admin_household_delete', methods: ['POST'])]
    public function deleteHousehold(
        Household $household,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_household'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if ($household->getTasks()->count() > 0 || $household->getEvents()->count() > 0 || $household->getExpenses()->count() > 0 || $household->getMessages()->count() > 0) {
            $this->addFlash('danger', 'No se puede eliminar una casa con tareas, eventos, gastos o mensajes vinculados.');

            return $this->redirectToRoute('app_admin_households');
        }

        $entityManager->remove($household);
        $entityManager->flush();
        $this->addFlash('success', 'Casa eliminada.');

        return $this->redirectToRoute('app_admin_households');
    }

    /** Renderiza una seccion concreta del panel de administracion de una casa. */
    #[Route('/households/{id}/{section}', name: 'app_admin_household', requirements: ['section' => 'summary|expenses|tasks|calendar|settings'], defaults: ['section' => 'summary'], methods: ['GET'])]
    public function household(
        Household $household,
        string $section,
        HouseholdRepository $householdRepository,
        TaskRepository $taskRepository,
        EventRepository $eventRepository,
        ExpenseRepository $expenseRepository,
        Request $request
    ): Response {
        $tasks = $taskRepository->findBy(['household' => $household, 'isActive' => true], ['id' => 'DESC']);
        $events = $eventRepository->findBy(['household' => $household, 'isActive' => true], ['startDate' => 'ASC']);
        $expenses = $expenseRepository->findBy(['household' => $household, 'isActive' => true], ['id' => 'DESC']);
        $completedTasks = array_filter($tasks, static fn ($task): bool => (bool) $task->isCompleted());
        $pendingExpenses = array_filter($expenses, static fn ($expense): bool => !$expense->isFullyPaidByAllParticipants());
        $requestedMonth = (string) $request->query->get('month', '');
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
            ? new \DateTimeImmutable($requestedMonth.'-01')
            : new \DateTimeImmutable('first day of this month');

        $stats = [
            'totalUsers' => count($household->getUsers()),
            'totalTasks' => count($tasks),
            'completedTasks' => count($completedTasks),
            'pendingTasks' => count($tasks) - count($completedTasks),
            'totalEvents' => count($events),
            'totalExpenses' => count($expenses),
            'unpaidExpenses' => count($pendingExpenses),
            'pendingAmount' => array_reduce($pendingExpenses, static fn (float $carry, $expense): float => $carry + (float) $expense->getAmount(), 0.0),
        ];

        return $this->render('admin/household.html.twig', [
            'household' => $household,
            'households' => $householdRepository->findBy([], ['id' => 'ASC']),
            'section' => $section,
            'stats' => $stats,
            'users' => $household->getUsers(),
            'memberships' => $household->getMembers(),
            'tasks' => $tasks,
            'events' => $events,
            'expenses' => $expenses,
            'calendarMonth' => $calendarMonth,
            'prevCalendarMonth' => $calendarMonth->modify('-1 month'),
            'nextCalendarMonth' => $calendarMonth->modify('+1 month'),
            'calendarWeeks' => $this->buildCalendarWeeks($calendarMonth, $events),
        ]);
    }

    /** Cambia el codigo de invitacion de una casa validando formato y unicidad. */
    #[Route('/households/{id}/invite-code', name: 'app_admin_household_invite_code', methods: ['POST'])]
    public function updateInviteCode(
        Household $household,
        Request $request,
        EntityManagerInterface $entityManager,
        HouseholdRepository $householdRepository
    ): Response {
        if (!$this->isCsrfTokenValid('invite_code'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $inviteCode = strtoupper(trim((string) $request->request->get('inviteCode')));
        if (!preg_match('/^[A-Z0-9]{4,10}$/', $inviteCode)) {
            $this->addFlash('danger', 'El código debe tener entre 4 y 10 caracteres alfanuméricos.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'settings']);
        }

        $existing = $householdRepository->findOneBy(['inviteCode' => $inviteCode]);
        if ($existing && $existing->getId() !== $household->getId()) {
            $this->addFlash('danger', 'Ese código ya está en uso por otra casa.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'settings']);
        }

        $household->setInviteCode($inviteCode);
        $entityManager->flush();
        $this->addFlash('success', 'Código de invitación actualizado.');

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'settings']);
    }

    /** Crea o actualiza un gasto, sus participantes y el estado de pago por persona. */
    #[Route('/households/{id}/expenses/save/{expenseId}', name: 'app_admin_expense_save', defaults: ['expenseId' => null], methods: ['POST'])]
    public function saveExpense(
        Household $household,
        ?int $expenseId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $expense = $expenseId ? $entityManager->getRepository(Expense::class)->find($expenseId) : new Expense();
        if (!$expense || ($expense->getId() && $expense->getHousehold()?->getId() !== $household->getId())) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('admin_expense'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $paymentType = (string) $request->request->get('paymentType', 'shared');
        $payer = null;
        if ($paymentType === 'individual') {
            $payer = $entityManager->getRepository(User::class)->find((int) $request->request->get('paidBy'));
            if (!$payer || !$this->userBelongsToHousehold($payer, $household)) {
                $this->addFlash('danger', 'El pagador debe ser miembro de la casa.');

                return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
            }
        }

        if ($this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'splitUsers') || $this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'paidShares')) {
            $this->addFlash('danger', 'Los participantes del gasto deben pertenecer a la casa.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
        }

        $periodicity = $request->request->get('periodicity') ?: null;
        if (!$this->isValidRecurrence($periodicity, $request)) {
            $this->addFlash('danger', 'Completa solo el dato de periodicidad que corresponde: día del mes, día semanal u hora diaria.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
        }

        if ($periodicity === null && trim((string) $request->request->get('paidAt')) === '') {
            $this->addFlash('danger', 'Indica la fecha de pago cuando el gasto no tiene periodicidad.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
        }

        if (trim((string) $request->request->get('title')) === '' || (float) $request->request->get('amount') <= 0) {
            $this->addFlash('danger', 'El gasto necesita titulo e importe positivo.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
        }

        $participants = $this->selectedHouseholdUsers($request, $household, $entityManager);
        if ($paymentType === 'individual') {
            $participants = [$payer];
        } elseif ([] === $participants) {
            $this->addFlash('danger', 'Selecciona al menos un participante para el gasto compartido.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
        } else {
            $payer = $participants[0];
        }

        $expense
            ->setHousehold($household)
            ->setTitle(trim((string) $request->request->get('title')))
            ->setDescription($request->request->get('description') ?: null)
            ->setAmount((string) $request->request->get('amount'))
            ->setCategory($request->request->get('category') ?: null)
            ->setPaymentType($paymentType)
            ->setPaidBy($payer)
            ->setPaidAt($request->request->get('paidAt') ? new \DateTime((string) $request->request->get('paidAt')) : null)
            ->setDueDate($request->request->get('dueDate') ? new \DateTime((string) $request->request->get('dueDate')) : null)
            ->setPeriodicity($periodicity)
            ->setRecurrenceDayOfMonth($periodicity === 'monthly' ? (int) $request->request->get('recurrenceDayOfMonth') : null)
            ->setRecurrenceWeekday($periodicity === 'weekly' ? (int) $request->request->get('recurrenceWeekday') : null)
            ->setRecurrenceTime($periodicity === 'daily' && $request->request->get('recurrenceTime') ? new \DateTime((string) $request->request->get('recurrenceTime')) : null)
            ->setIsPaid((bool) $request->request->get('isPaid', false))
            ->setNotes($request->request->get('notes') ?: null);

        $this->replaceExpenseShares(
            $expense,
            $participants,
            $paymentType === 'individual' && (bool) $request->request->get('isPaid', false),
            array_map('intval', (array) $request->request->all('paidShares'))
        );

        $entityManager->persist($expense);
        $entityManager->flush();
        $this->addFlash('success', 'Gasto guardado.');

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
    }

    /** Elimina un gasto solo si pertenece a la casa actual. */
    #[Route('/households/{id}/expenses/{expenseId}/delete', name: 'app_admin_expense_delete', methods: ['POST'])]
    public function deleteExpense(Household $household, int $expenseId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $expense = $entityManager->getRepository(Expense::class)->find($expenseId);
        if ($expense && $expense->getHousehold()?->getId() === $household->getId() && $this->isCsrfTokenValid('delete_expense'.$expenseId, (string) $request->request->get('_token'))) {
            $expense->setIsActive(false);
            $entityManager->flush();
            $this->addFlash('success', 'Gasto dado de baja.');
        }

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'expenses']);
    }

    /** Crea o actualiza una tarea de la casa. */
    #[Route('/households/{id}/tasks/save/{taskId}', name: 'app_admin_task_save', defaults: ['taskId' => null], methods: ['POST'])]
    public function saveTask(Household $household, ?int $taskId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $taskId ? $entityManager->getRepository(Task::class)->find($taskId) : new Task();
        if (!$task || ($task->getId() && $task->getHousehold()?->getId() !== $household->getId())) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('admin_task'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $assignedTo = $entityManager->getRepository(User::class)->find((int) $request->request->get('assignedTo'));
        if (!$assignedTo || !$this->userBelongsToHousehold($assignedTo, $household)) {
            $this->addFlash('danger', 'La persona asignada debe ser miembro de la casa.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'tasks']);
        }

        if (trim((string) $request->request->get('title')) === '') {
            $this->addFlash('danger', 'La tarea necesita un titulo.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'tasks']);
        }

        $task
            ->setHousehold($household)
            ->setAssignedTo($assignedTo)
            ->setTitle(trim((string) $request->request->get('title')))
            ->setDescription($request->request->get('description') ?: null)
            ->setDueDate($request->request->get('dueDate') ? new \DateTime((string) $request->request->get('dueDate')) : null)
            ->setPriority($request->request->get('priority') ?: 'Mitja')
            ->setCategory($request->request->get('category') ?: null)
            ->setPeriodicity($request->request->get('periodicity') ?: null)
            ->setCompleted((bool) $request->request->get('completed', false));

        if (!$task->getCreatedAt()) {
            $task->setCreatedAt(new \DateTime());
        }

        $entityManager->persist($task);
        $entityManager->flush();
        $this->addFlash('success', 'Tarea guardada.');

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'tasks']);
    }

    /** Elimina una tarea solo si pertenece a la casa actual. */
    #[Route('/households/{id}/tasks/{taskId}/delete', name: 'app_admin_task_delete', methods: ['POST'])]
    public function deleteTask(Household $household, int $taskId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($taskId);
        if ($task && $task->getHousehold()?->getId() === $household->getId() && $this->isCsrfTokenValid('delete_task'.$taskId, (string) $request->request->get('_token'))) {
            $task->setIsActive(false);
            $entityManager->flush();
            $this->addFlash('success', 'Tarea dada de baja.');
        }

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'tasks']);
    }

    /** Crea o actualiza un evento y sus participantes. */
    #[Route('/households/{id}/events/save/{eventId}', name: 'app_admin_event_save', defaults: ['eventId' => null], methods: ['POST'])]
    public function saveEvent(Household $household, ?int $eventId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $eventId ? $entityManager->getRepository(Event::class)->find($eventId) : new Event();
        if (!$event || ($event->getId() && $event->getHousehold()?->getId() !== $household->getId())) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('admin_event'.$household->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $creator */
        $creator = $this->getUser();
        if ($this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'participants')) {
            $this->addFlash('danger', 'Los participantes del evento deben pertenecer a la casa.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'calendar']);
        }

        $startDate = new \DateTime((string) $request->request->get('startDate'));
        $endDate = $request->request->get('endDate') ? new \DateTime((string) $request->request->get('endDate')) : null;
        if ($endDate && $endDate <= $startDate) {
            $this->addFlash('danger', 'La fecha de fin debe ser posterior al inicio.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'calendar']);
        }

        if (trim((string) $request->request->get('title')) === '') {
            $this->addFlash('danger', 'El evento necesita titulo.');

            return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'calendar']);
        }

        $event
            ->setHousehold($household)
            ->setCreatedBy($event->getCreatedBy() ?: $creator)
            ->setTitle(trim((string) $request->request->get('title')))
            ->setDescription($request->request->get('description') ?: null)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setLocation($request->request->get('location') ?: null)
            ->setColor($request->request->get('color') ?: '#ea580c')
            ->setIsAllDay((bool) $request->request->get('isAllDay', false));

        foreach ($event->getParticipants()->toArray() as $participant) {
            $event->removeParticipant($participant);
        }
        foreach ($this->selectedHouseholdUsers($request, $household, $entityManager, 'participants') as $participant) {
            $event->addParticipant($participant);
        }

        $entityManager->persist($event);
        $entityManager->flush();
        $this->addFlash('success', 'Evento guardado.');

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'calendar']);
    }

    /** Elimina un evento solo si pertenece a la casa actual. */
    #[Route('/households/{id}/events/{eventId}/delete', name: 'app_admin_event_delete', methods: ['POST'])]
    public function deleteEvent(Household $household, int $eventId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($eventId);
        if ($event && $event->getHousehold()?->getId() === $household->getId() && $this->isCsrfTokenValid('delete_event'.$eventId, (string) $request->request->get('_token'))) {
            $event->setIsActive(false);
            $entityManager->flush();
            $this->addFlash('success', 'Evento dado de baja.');
        }

        return $this->redirectToRoute('app_admin_household', ['id' => $household->getId(), 'section' => 'calendar']);
    }

    /** Comprueba que un usuario sea miembro de una casa. */
    private function userBelongsToHousehold(User $user, Household $household): bool
    {
        foreach ($user->getHouseholdMemberships() as $membership) {
            if ($membership->getHousehold()?->getId() === $household->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convierte IDs de formulario en usuarios reales de la casa.
     *
     * @return User[]
     */
    private function selectedHouseholdUsers(Request $request, Household $household, EntityManagerInterface $entityManager, string $field = 'splitUsers'): array
    {
        $users = [];
        foreach ((array) $request->request->all($field) as $userId) {
            $user = $entityManager->getRepository(User::class)->find((int) $userId);
            if ($user && $this->userBelongsToHousehold($user, $household)) {
                $users[$user->getId()] = $user;
            }
        }

        return array_values($users);
    }

    /** Detecta IDs manipulados o de usuarios que no son miembros de la casa. */
    private function hasInvalidHouseholdUsers(Request $request, Household $household, EntityManagerInterface $entityManager, string $field): bool
    {
        foreach ((array) $request->request->all($field) as $userId) {
            $user = $entityManager->getRepository(User::class)->find((int) $userId);
            if (!$user || !$this->userBelongsToHousehold($user, $household)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recalcula las cuotas de un gasto compartido y marca pagados.
     *
     * @param User[] $participants
     */
    private function replaceExpenseShares(Expense $expense, array $participants, bool $paid, array $paidShareUserIds = []): void
    {
        $expense->removeAllExpenseShares();
        if ([] === $participants) {
            return;
        }

        $amount = round(((float) $expense->getAmount()) / count($participants), 2);
        foreach ($participants as $participant) {
            $participantPaid = $paid || in_array($participant->getId(), $paidShareUserIds, true);
            $share = (new ExpenseShare())
                ->setUser($participant)
                ->setAmountOwed((string) $amount)
                ->setIsPaid($participantPaid)
                ->setPaidAt($participantPaid ? new \DateTime() : null);
            $expense->addExpenseShare($share);
        }
    }

    /** Valida que cada periodicidad tenga solo el campo que le corresponde. */
    private function isValidRecurrence(?string $periodicity, Request $request): bool
    {
        return match ($periodicity) {
            null, '' => true,
            'monthly' => (int) $request->request->get('recurrenceDayOfMonth') >= 1 && (int) $request->request->get('recurrenceDayOfMonth') <= 31,
            'weekly' => (int) $request->request->get('recurrenceWeekday') >= 1 && (int) $request->request->get('recurrenceWeekday') <= 7,
            'daily' => trim((string) $request->request->get('recurrenceTime')) !== '',
            default => false,
        };
    }

    /** Construye las semanas visibles de un mes para pintar el calendario. */
    private function buildCalendarWeeks(\DateTimeImmutable $month, array $events): array
    {
        $first = $month->modify('first day of this month');
        $last = $month->modify('last day of this month');
        $cursor = $first->modify('-'.((int) $first->format('N') - 1).' days');
        $end = $last->modify('+'.(7 - (int) $last->format('N')).' days');
        $eventsByDay = [];

        foreach ($events as $event) {
            $key = $event->getStartDate()->format('Y-m-d');
            $eventsByDay[$key][] = $event;
        }

        $weeks = [];
        while ($cursor <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');
                $week[] = [
                    'date' => $cursor,
                    'inMonth' => $cursor->format('m') === $month->format('m'),
                    'events' => $eventsByDay[$key] ?? [],
                ];
                $cursor = $cursor->modify('+1 day');
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /** Lista todos los usuarios para gestionarlos desde modales. */
    #[Route('/users', name: 'app_admin_users')]
    public function users(
        Request $request,
        UserRepository $userRepository,
        HouseholdRepository $householdRepository
    ): Response
    {
        $users = $this->filterUsers($userRepository, $request);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'households' => $householdRepository->findBy([], ['name' => 'ASC']),
            'filters' => $request->query->all(),
            'avatarIcons' => $this->avatarIcons(),
        ]);
    }

    /** Guarda cambios de usuario, rol, foto/avatar y password opcional. */
    #[Route('/users/{id}/save', name: 'app_admin_user_save', methods: ['POST'])]
    public function saveUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (!$this->isCsrfTokenValid('admin_user'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $newEmail = trim((string) $request->request->get('email'));
        $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
        if ($existing && $existing->getId() !== $user->getId()) {
            $this->addFlash('danger', 'Ese email ya esta en uso.');

            return $this->redirectToRoute('app_admin_users');
        }

        $avatarCropData = (string) $request->request->get('avatarCropData');
        if ($avatarCropData !== '') {
            $avatarPath = $this->storeAvatarCrop($avatarCropData);
            if ($avatarPath === null) {
                $this->addFlash('danger', 'No se pudo procesar el recorte de la foto.');

                return $this->redirectToRoute('app_admin_users');
            }

            $this->deleteAvatarFile($user->getAvatar());
            $user->setAvatar($avatarPath);
        } else {
            $avatarFile = $request->files->get('avatarFile');
            if ($avatarFile instanceof UploadedFile) {
                $avatarPath = $this->storeAvatarUpload($avatarFile);
                if ($avatarPath === null) {
                    $this->addFlash('danger', 'La foto debe ser una imagen valida de maximo 2 MB.');

                    return $this->redirectToRoute('app_admin_users');
                }

                $this->deleteAvatarFile($user->getAvatar());
                $user->setAvatar($avatarPath);
            }
        }

        if ($request->request->getBoolean('removeAvatar')) {
            $this->deleteAvatarFile($user->getAvatar());
            $user->setAvatar(null);
        }

        $roles = ['ROLE_USER'];
        $role = (string) $request->request->get('role', 'ROLE_USER');
        if (in_array($role, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], true)) {
            $roles[] = $role;
        }

        $user
            ->setFirstName(trim((string) $request->request->get('firstName')))
            ->setLastName(trim((string) $request->request->get('lastName')))
            ->setEmail($newEmail)
            ->setPhoneNumber($request->request->get('phoneNumber') ?: null)
            ->setBio($request->request->get('bio') ?: null)
            ->setAvatarIcon($request->request->get('avatarIcon') ?: 'pi-user')
            ->setRoles($roles)
            ->setIsActive((bool) $request->request->get('isActive', false));

        $newPassword = trim((string) $request->request->get('newPassword'));
        if ($newPassword !== '') {
            if (strlen($newPassword) < 6) {
                $this->addFlash('danger', 'La contrasena debe tener al menos 6 caracteres.');

                return $this->redirectToRoute('app_admin_users');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        }

        $entityManager->flush();
        $this->addFlash('success', 'Usuario actualizado.');

        return $this->redirectToRoute('app_admin_users');
    }

    /** Crea usuarios usando el formulario Symfony clasico. */
    #[Route('/users/new', name: 'app_admin_user_new')]
    public function newUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $user->setIsActive(true);
        $form = $this->createForm(UserType::class, $user, [
            'allow_household' => true,
            'allow_global_role' => true,
            'allow_active' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            // Defaults
            $user->setJoinedAt(new \DateTime());
            $user->setAvatarIcon('pi-user');
            $role = (string) $form->get('role')->getData();
            $roles = ['ROLE_USER'];
            if (in_array($role, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], true)) {
                $roles[] = $role;
            }
            $user->setRoles($roles);

            $entityManager->persist($user);
            $household = $form->get('household')->getData();
            if ($household instanceof Household) {
                $membership = (new HouseholdMember())
                    ->setUser($user)
                    ->setHousehold($household)
                    ->setRole('member');
                $entityManager->persist($membership);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Usuari creat correctament!');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /** Edita usuarios usando el formulario Symfony clasico. */
    #[Route('/users/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Usuari actualitzat correctament!');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /** Borra un usuario evitando que el superadmin se elimine a si mismo. */
    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $admin */
        $admin = $this->getUser();

        if ($user === $admin) {
            $this->addFlash('danger', 'No pots esborrar el teu propi compte!');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(false);
            $entityManager->flush();
            $this->addFlash('success', 'Usuari donat de baixa correctament!');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    /** Activa o desactiva rapidamente un usuario. */
    #[Route('/users/{id}/toggle-active', name: 'app_admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', 'L’usuari ha estat actualitzat correctament!');

        return $this->redirectToRoute('app_admin_users');
    }

    /** Devuelve los iconos de avatar permitidos para backend y frontend. */
    private function avatarIcons(): array
    {
        return ['pi-user', 'pi-home', 'pi-star', 'pi-heart', 'pi-sparkles', 'pi-briefcase', 'pi-crown', 'pi-face-smile', 'pi-bolt', 'pi-sun'];
    }

    /** Devuelve los iconos permitidos para representar casas. */
    private function householdAvatarIcons(): array
    {
        return ['pi-home', 'pi-building', 'pi-users', 'pi-heart', 'pi-star', 'pi-briefcase', 'pi-sparkles', 'pi-crown', 'pi-map-marker', 'pi-key'];
    }

    /** Procesa la foto de una casa usando el mismo recorte cuadrado que los usuarios. */
    private function applyHouseholdAvatar(Household $household, Request $request): bool
    {
        $avatarCropData = (string) $request->request->get('avatarCropData');
        if ($avatarCropData !== '') {
            $avatarPath = $this->storeAvatarCrop($avatarCropData);
            if ($avatarPath === null) {
                $this->addFlash('danger', 'No se pudo procesar el recorte de la foto.');

                return false;
            }

            $this->deleteAvatarFile($household->getAvatar());
            $household->setAvatar($avatarPath);
        } else {
            $avatarFile = $request->files->get('avatarFile');
            if ($avatarFile instanceof UploadedFile) {
                $avatarPath = $this->storeAvatarUpload($avatarFile);
                if ($avatarPath === null) {
                    $this->addFlash('danger', 'La foto debe ser una imagen valida de maximo 2 MB.');

                    return false;
                }

                $this->deleteAvatarFile($household->getAvatar());
                $household->setAvatar($avatarPath);
            }
        }

        if ($request->request->getBoolean('removeAvatar')) {
            $this->deleteAvatarFile($household->getAvatar());
            $household->setAvatar(null);
        }

        return true;
    }

    /** Crea una tarea desde el grid global seleccionando casa destino. */
    #[Route('/tasks/create', name: 'app_admin_task_global_create', methods: ['POST'])]
    public function createGlobalTask(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_global_task', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $household = $entityManager->getRepository(Household::class)->find((int) $request->request->get('householdId'));
        if (!$household) {
            $this->addFlash('danger', 'Selecciona una casa valida para la tarea.');

            return $this->redirectToRoute('app_admin_tasks');
        }

        $assignedTo = $entityManager->getRepository(User::class)->find((int) $request->request->get('assignedTo'));
        if (!$assignedTo || !$this->userBelongsToHousehold($assignedTo, $household)) {
            $this->addFlash('danger', 'La persona asignada debe ser miembro de la casa seleccionada.');

            return $this->redirectToRoute('app_admin_tasks');
        }

        $title = trim((string) $request->request->get('title'));
        if ($title === '') {
            $this->addFlash('danger', 'La tarea necesita un titulo.');

            return $this->redirectToRoute('app_admin_tasks');
        }

        $priority = (string) $request->request->get('priority', 'Mitja');
        if (!in_array($priority, ['Baixa', 'Mitja', 'Alta'], true)) {
            $priority = 'Mitja';
        }

        $task = (new Task())
            ->setHousehold($household)
            ->setAssignedTo($assignedTo)
            ->setTitle($title)
            ->setDescription($request->request->get('description') ?: null)
            ->setCategory($request->request->get('category') ?: null)
            ->setDueDate($request->request->get('dueDate') ? new \DateTime((string) $request->request->get('dueDate')) : null)
            ->setPriority($priority)
            ->setPeriodicity($request->request->get('periodicity') ?: null)
            ->setCompleted((bool) $request->request->get('completed', false))
            ->setCreatedAt(new \DateTime());

        $entityManager->persist($task);
        $entityManager->flush();
        $this->addFlash('success', 'Tarea creada.');

        return $this->redirectToRoute('app_admin_tasks');
    }

    /** Crea un gasto desde el grid global seleccionando casa destino. */
    #[Route('/expenses/create', name: 'app_admin_expense_global_create', methods: ['POST'])]
    public function createGlobalExpense(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_global_expense', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $household = $entityManager->getRepository(Household::class)->find((int) $request->request->get('householdId'));
        if (!$household) {
            $this->addFlash('danger', 'Selecciona una casa valida para el gasto.');

            return $this->redirectToRoute('app_admin_expenses');
        }

        $paymentType = (string) $request->request->get('paymentType', 'shared');
        $payer = null;
        if ($paymentType === 'individual') {
            $payer = $entityManager->getRepository(User::class)->find((int) $request->request->get('paidBy'));
            if (!$payer || !$this->userBelongsToHousehold($payer, $household)) {
                $this->addFlash('danger', 'El pagador debe ser miembro de la casa seleccionada.');

                return $this->redirectToRoute('app_admin_expenses');
            }
        }

        $title = trim((string) $request->request->get('title'));
        $amount = (float) $request->request->get('amount');
        if ($title === '' || $amount <= 0) {
            $this->addFlash('danger', 'El gasto necesita titulo e importe positivo.');

            return $this->redirectToRoute('app_admin_expenses');
        }

        $periodicity = $request->request->get('periodicity') ?: null;
        if (!$this->isValidRecurrence($periodicity, $request)) {
            $this->addFlash('danger', 'Completa solo el dato de periodicidad que corresponde: dia del mes, dia semanal u hora diaria.');

            return $this->redirectToRoute('app_admin_expenses');
        }

        if ($periodicity === null && trim((string) $request->request->get('paidAt')) === '') {
            $this->addFlash('danger', 'Indica la fecha de pago cuando el gasto no tiene periodicidad.');

            return $this->redirectToRoute('app_admin_expenses');
        }

        if ($this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'splitUsers') || $this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'paidShares')) {
            $this->addFlash('danger', 'Los participantes del gasto deben pertenecer a la casa seleccionada.');

            return $this->redirectToRoute('app_admin_expenses');
        }

        $participants = $this->selectedHouseholdUsers($request, $household, $entityManager);
        if ($paymentType === 'individual') {
            $participants = [$payer];
        } elseif ([] === $participants) {
            $this->addFlash('danger', 'Selecciona al menos un participante para el gasto compartido.');

            return $this->redirectToRoute('app_admin_expenses');
        } else {
            $payer = $participants[0];
        }

        $expense = (new Expense())
            ->setHousehold($household)
            ->setTitle($title)
            ->setDescription($request->request->get('description') ?: null)
            ->setAmount((string) $request->request->get('amount'))
            ->setCategory($request->request->get('category') ?: null)
            ->setPaymentType($paymentType)
            ->setPaidBy($payer)
            ->setPaidAt($request->request->get('paidAt') ? new \DateTime((string) $request->request->get('paidAt')) : null)
            ->setDueDate($request->request->get('dueDate') ? new \DateTime((string) $request->request->get('dueDate')) : null)
            ->setPeriodicity($periodicity)
            ->setRecurrenceDayOfMonth($periodicity === 'monthly' ? (int) $request->request->get('recurrenceDayOfMonth') : null)
            ->setRecurrenceWeekday($periodicity === 'weekly' ? (int) $request->request->get('recurrenceWeekday') : null)
            ->setRecurrenceTime($periodicity === 'daily' && $request->request->get('recurrenceTime') ? new \DateTime((string) $request->request->get('recurrenceTime')) : null)
            ->setIsPaid((bool) $request->request->get('isPaid', false))
            ->setNotes($request->request->get('notes') ?: null);

        $this->replaceExpenseShares(
            $expense,
            $participants,
            $paymentType === 'individual' && (bool) $request->request->get('isPaid', false),
            array_map('intval', (array) $request->request->all('paidShares'))
        );

        $entityManager->persist($expense);
        $entityManager->flush();
        $this->addFlash('success', 'Gasto creado.');

        return $this->redirectToRoute('app_admin_expenses');
    }

    /** Crea un evento desde el grid global seleccionando casa destino. */
    #[Route('/events/create', name: 'app_admin_event_global_create', methods: ['POST'])]
    public function createGlobalEvent(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('admin_global_event', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $household = $entityManager->getRepository(Household::class)->find((int) $request->request->get('householdId'));
        if (!$household) {
            $this->addFlash('danger', 'Selecciona una casa valida para el evento.');

            return $this->redirectToRoute('app_admin_events');
        }

        $title = trim((string) $request->request->get('title'));
        if ($title === '' || !$request->request->get('startDate')) {
            $this->addFlash('danger', 'El evento necesita titulo y fecha de inicio.');

            return $this->redirectToRoute('app_admin_events');
        }

        $startDate = new \DateTime((string) $request->request->get('startDate'));
        $endDate = $request->request->get('endDate') ? new \DateTime((string) $request->request->get('endDate')) : null;
        if ($endDate && $endDate <= $startDate) {
            $this->addFlash('danger', 'La fecha de fin debe ser posterior al inicio.');

            return $this->redirectToRoute('app_admin_events');
        }

        if ($this->hasInvalidHouseholdUsers($request, $household, $entityManager, 'participants')) {
            $this->addFlash('danger', 'Los participantes del evento deben pertenecer a la casa seleccionada.');

            return $this->redirectToRoute('app_admin_events');
        }

        /** @var User $creator */
        $creator = $this->getUser();
        $event = (new Event())
            ->setHousehold($household)
            ->setCreatedBy($creator)
            ->setTitle($title)
            ->setDescription($request->request->get('description') ?: null)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setLocation($request->request->get('location') ?: null)
            ->setColor($request->request->get('color') ?: '#ea580c')
            ->setIsAllDay((bool) $request->request->get('isAllDay', false));

        foreach ($this->selectedHouseholdUsers($request, $household, $entityManager, 'participants') as $participant) {
            $event->addParticipant($participant);
        }

        $entityManager->persist($event);
        $entityManager->flush();
        $this->addFlash('success', 'Evento creado.');

        return $this->redirectToRoute('app_admin_events');
    }

    #[Route('/tasks', name: 'app_admin_tasks')]
    public function allTasks(Request $request, TaskRepository $taskRepository, HouseholdRepository $householdRepository, UserRepository $userRepository): Response
    {
        $qb = $taskRepository->createQueryBuilder('t')
            ->leftJoin('t.household', 'h')->addSelect('h')
            ->leftJoin('t.assignedTo', 'u')->addSelect('u')
            ->orderBy('t.id', 'DESC');
        $this->applyCommonRecordFilters($qb, $request, 't', true);
        if ($request->query->get('assignedTo')) {
            $qb->andWhere('u.id = :assignedTo')->setParameter('assignedTo', (int) $request->query->get('assignedTo'));
        }

        return $this->render('admin/records.html.twig', [
            'type' => 'tasks',
            'title' => 'Tareas',
            'icon' => 'pi-check-square',
            'records' => $qb->getQuery()->getResult(),
            'households' => $householdRepository->findBy([], ['name' => 'ASC']),
            'users' => $userRepository->findBy([], ['firstName' => 'ASC']),
            'filters' => $request->query->all(),
        ]);
    }

    #[Route('/expenses', name: 'app_admin_expenses')]
    public function allExpenses(Request $request, ExpenseRepository $expenseRepository, HouseholdRepository $householdRepository, UserRepository $userRepository): Response
    {
        $qb = $expenseRepository->createQueryBuilder('e')
            ->leftJoin('e.household', 'h')->addSelect('h')
            ->leftJoin('e.paidBy', 'u')->addSelect('u')
            ->orderBy('e.id', 'DESC');
        $this->applyCommonRecordFilters($qb, $request, 'e', true);
        if ($request->query->get('createdBy')) {
            $qb->andWhere('u.id = :createdBy')->setParameter('createdBy', (int) $request->query->get('createdBy'));
        }

        return $this->render('admin/records.html.twig', [
            'type' => 'expenses',
            'title' => 'Gastos',
            'icon' => 'pi-wallet',
            'records' => $qb->getQuery()->getResult(),
            'households' => $householdRepository->findBy([], ['name' => 'ASC']),
            'users' => $userRepository->findBy([], ['firstName' => 'ASC']),
            'filters' => $request->query->all(),
        ]);
    }

    #[Route('/events', name: 'app_admin_events')]
    public function allEvents(Request $request, EventRepository $eventRepository, HouseholdRepository $householdRepository, UserRepository $userRepository): Response
    {
        $qb = $eventRepository->createQueryBuilder('e')
            ->leftJoin('e.household', 'h')->addSelect('h')
            ->leftJoin('e.createdBy', 'u')->addSelect('u')
            ->orderBy('e.startDate', 'DESC');
        $this->applyCommonRecordFilters($qb, $request, 'e', true);
        if ($request->query->get('createdBy')) {
            $qb->andWhere('u.id = :createdBy')->setParameter('createdBy', (int) $request->query->get('createdBy'));
        }

        return $this->render('admin/records.html.twig', [
            'type' => 'events',
            'title' => 'Eventos',
            'icon' => 'pi-calendar',
            'records' => $qb->getQuery()->getResult(),
            'households' => $householdRepository->findBy([], ['name' => 'ASC']),
            'users' => $userRepository->findBy([], ['firstName' => 'ASC']),
            'filters' => $request->query->all(),
        ]);
    }

    private function filterUsers(UserRepository $userRepository, Request $request): array
    {
        $qb = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.householdMemberships', 'hm')
            ->leftJoin('hm.household', 'h')
            ->addSelect('hm', 'h')
            ->orderBy('u.id', 'ASC');
        if ($request->query->get('status') === 'active') {
            $qb->andWhere('u.isActive = true');
        } elseif ($request->query->get('status') === 'inactive') {
            $qb->andWhere('u.isActive = false');
        }
        if ($request->query->get('household')) {
            $qb->andWhere('h.id = :household')->setParameter('household', (int) $request->query->get('household'));
        }
        if ($request->query->get('role')) {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%'.$request->query->get('role').'%');
        }
        if ($request->query->get('q')) {
            $qb->andWhere('LOWER(u.email) LIKE :q OR LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower((string) $request->query->get('q')).'%');
        }

        return $qb->getQuery()->getResult();
    }

    private function applyCommonRecordFilters(\Doctrine\ORM\QueryBuilder $qb, Request $request, string $alias, bool $hasHousehold): void
    {
        if ($request->query->get('status') === 'active') {
            $qb->andWhere($alias.'.isActive = true');
        } elseif ($request->query->get('status') === 'inactive') {
            $qb->andWhere($alias.'.isActive = false');
        }
        if ($hasHousehold && $request->query->get('household')) {
            $qb->andWhere('h.id = :household')->setParameter('household', (int) $request->query->get('household'));
        }
        if ($request->query->get('q')) {
            $qb->andWhere('LOWER('.$alias.'.title) LIKE :q')->setParameter('q', '%'.mb_strtolower((string) $request->query->get('q')).'%');
        }
    }

    /** Genera un codigo de invitacion que no exista en otra casa. */
    private function generateInviteCode(HouseholdRepository $householdRepository): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($householdRepository->findOneBy(['inviteCode' => $code]));

        return $code;
    }

    /** Guarda una imagen subida sin depender del componente Symfony Mime. */
    private function storeAvatarUpload(UploadedFile $file): ?string
    {
        if ($file->getSize() > 2 * 1024 * 1024 || !@getimagesize($file->getPathname())) {
            return null;
        }

        $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $extension = 'jpg';
        }

        $filename = bin2hex(random_bytes(8)).'.'.$extension;
        $directory = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $file->move($directory, $filename);

        return '/uploads/avatars/'.$filename;
    }

    /** Guarda el recorte cuadrado enviado por canvas desde el navegador. */
    private function storeAvatarCrop(string $dataUrl): ?string
    {
        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            return null;
        }

        $bytes = base64_decode($matches[1], true);
        if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024 || !@getimagesizefromstring($bytes)) {
            return null;
        }

        $directory = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(8)).'.png';
        file_put_contents($directory.'/'.$filename, $bytes);

        return '/uploads/avatars/'.$filename;
    }

    /** Borra una foto de perfil local si pertenece al directorio de avatares. */
    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (!$avatarPath || !str_starts_with($avatarPath, '/uploads/avatars/')) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir').'/public'.$avatarPath;
        $realPath = realpath($fullPath);
        $avatarDir = realpath($this->getParameter('kernel.project_dir').'/public/uploads/avatars');
        if ($realPath && $avatarDir && str_starts_with($realPath, $avatarDir) && is_file($realPath)) {
            unlink($realPath);
        }
    }
}
