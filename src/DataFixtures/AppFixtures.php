<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Expense;
use App\Entity\ExpenseShare;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Notification;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures de demo para la presentación técnica de HomeTab (GenSync).
 *
 * Cubre TODAS las funcionalidades demostrables:
 *   - Casas (Households) con icono y código de invitación
 *   - Usuarios con roles, bio y avatar
 *   - Membresías owner/member
 *   - Gastos compartidos e individuales, con y sin periodicidad, pendientes y pagados
 *   - Tareas con distintas prioridades, categorías y estados
 *   - Eventos en el calendario (pasados, hoy, futuros, todo-el-dia)
 *   - Notificaciones activas
 *
 * Contraseña universal de demo: HomeTab2026!
 */
class AppFixtures extends Fixture
{
    private const DEMO_PASSWORD = 'HomeTab2026!';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    // ─────────────────────────────────────────────────────────
    // ENTRY POINT
    // ─────────────────────────────────────────────────────────

    public function load(ObjectManager $m): void
    {
        [$naomi, $joan, $anna, $pau, $sara] = $this->createUsers($m);
        $superAdmin = $this->createSuperAdmin($m);

        [$casaHome, $pisBcn, $casaRural] = $this->createHouseholds($m);

        $this->createMemberships($m, $casaHome, $pisBcn, $casaRural, $naomi, $joan, $anna, $pau, $sara);

        $this->createTasksCasaHome($m, $casaHome, $naomi, $joan, $anna);
        $this->createTasksPisBcn($m, $pisBcn, $pau, $sara);
        $this->createTasksCasaRural($m, $casaRural, $naomi, $pau);

        $this->createExpensesCasaHome($m, $casaHome, $naomi, $joan, $anna);
        $this->createExpensesPisBcn($m, $pisBcn, $pau, $sara);
        $this->createExpensesCasaRural($m, $casaRural, $naomi, $pau);

        $this->createEventsCasaHome($m, $casaHome, $naomi, $joan, $anna);
        $this->createEventsPisBcn($m, $pisBcn, $pau, $sara);
        $this->createEventsCasaRural($m, $casaRural, $naomi, $pau);

        $this->createNotifications($m, $naomi, $joan, $anna, $pau, $sara, $casaHome, $pisBcn, $casaRural);

        $m->flush();
    }

    // ─────────────────────────────────────────────────────────
    // SUPERADMIN
    // ─────────────────────────────────────────────────────────

    private function createSuperAdmin(ObjectManager $m): User
    {
        $u = new User();
        $u->setEmail('hometab.admin@gmail.com');
        $u->setRoles(['ROLE_SUPER_ADMIN']);
        $u->setFirstName('Admin');
        $u->setLastName('HomeTab');
        $u->setBio('Superadministrador de la plataforma HomeTab.');
        $u->setJoinedAt(new \DateTime('-2 years'));
        $u->setIsActive(true);
        $u->setTwoFactorEnabled(false);
        $u->setAvatarIcon('pi-crown');
        $u->setPassword($this->passwordHasher->hashPassword($u, self::DEMO_PASSWORD));
        $m->persist($u);
        return $u;
    }

    // ─────────────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────────────

    private function createUsers(ObjectManager $m): array
    {
        // Naomi — propietaria principal (email real del proyecto)
        $naomi = new User();
        $naomi->setEmail('naoctubre@gmail.com');
        $naomi->setRoles(['ROLE_USER']);
        $naomi->setFirstName('Naomi');
        $naomi->setLastName('Octubre');
        $naomi->setPhoneNumber('+34 612 345 678');
        $naomi->setBio('Organizo las casas y nunca olvido pagar las facturas. 🏠');
        $naomi->setJoinedAt(new \DateTime('-8 months'));
        $naomi->setIsActive(true);
        $naomi->setTwoFactorEnabled(false);
        $naomi->setAvatarIcon('pi-sparkles');
        $naomi->setPassword($this->passwordHasher->hashPassword($naomi, self::DEMO_PASSWORD));
        $m->persist($naomi);

        // Joan — co-desarrollador (email real del proyecto)
        $joan = new User();
        $joan->setEmail('jamon141006@gmail.com');
        $joan->setRoles(['ROLE_USER']);
        $joan->setFirstName('Joan');
        $joan->setLastName('Vilar');
        $joan->setPhoneNumber('+34 623 456 789');
        $joan->setBio('Dev & roommate. Me encargan siempre la compra. 🛒');
        $joan->setJoinedAt(new \DateTime('-8 months'));
        $joan->setIsActive(true);
        $joan->setTwoFactorEnabled(false);
        $joan->setAvatarIcon('pi-bolt');
        $joan->setPassword($this->passwordHasher->hashPassword($joan, self::DEMO_PASSWORD));
        $m->persist($joan);

        // Anna — vecina activa
        $anna = new User();
        $anna->setEmail('anna.demo@hometab.app');
        $anna->setRoles(['ROLE_USER']);
        $anna->setFirstName('Anna');
        $anna->setLastName('López');
        $anna->setPhoneNumber('+34 634 567 890');
        $anna->setBio('Fanática del orden y el Bullet Journal. 📓');
        $anna->setJoinedAt(new \DateTime('-6 months'));
        $anna->setIsActive(true);
        $anna->setTwoFactorEnabled(false);
        $anna->setAvatarIcon('pi-heart');
        $anna->setPassword($this->passwordHasher->hashPassword($anna, self::DEMO_PASSWORD));
        $m->persist($anna);

        // Pau — nuevo inquilino
        $pau = new User();
        $pau->setEmail('pau.demo@hometab.app');
        $pau->setRoles(['ROLE_USER']);
        $pau->setFirstName('Pau');
        $pau->setLastName('Mas');
        $pau->setPhoneNumber('+34 645 678 901');
        $pau->setBio('Chef amateur y amante del café. ☕');
        $pau->setJoinedAt(new \DateTime('-4 months'));
        $pau->setIsActive(true);
        $pau->setTwoFactorEnabled(false);
        $pau->setAvatarIcon('pi-face-smile');
        $pau->setPassword($this->passwordHasher->hashPassword($pau, self::DEMO_PASSWORD));
        $m->persist($pau);

        // Sara — roommate del piso BCN
        $sara = new User();
        $sara->setEmail('sara.demo@hometab.app');
        $sara->setRoles(['ROLE_USER']);
        $sara->setFirstName('Sara');
        $sara->setLastName('Roca');
        $sara->setPhoneNumber('+34 656 789 012');
        $sara->setBio('Arquitecta. Me encargo del diseño del piso. 🏛️');
        $sara->setJoinedAt(new \DateTime('-3 months'));
        $sara->setIsActive(true);
        $sara->setTwoFactorEnabled(false);
        $sara->setAvatarIcon('pi-star');
        $sara->setPassword($this->passwordHasher->hashPassword($sara, self::DEMO_PASSWORD));
        $m->persist($sara);

        return [$naomi, $joan, $anna, $pau, $sara];
    }

    // ─────────────────────────────────────────────────────────
    // CASAS (HOUSEHOLDS)
    // ─────────────────────────────────────────────────────────

    private function createHouseholds(ObjectManager $m): array
    {
        $casaHome = new Household();
        $casaHome->setName('Casa Home');
        $casaHome->setInviteCode('CASAHOME');
        $casaHome->setAvatarIcon('pi-home');
        $m->persist($casaHome);

        $pisBcn = new Household();
        $pisBcn->setName('Pis Barcelona');
        $pisBcn->setInviteCode('PISBCN');
        $pisBcn->setAvatarIcon('pi-building');
        $m->persist($pisBcn);

        $casaRural = new Household();
        $casaRural->setName('Casa Rural Montilivi');
        $casaRural->setInviteCode('RURAL01');
        $casaRural->setAvatarIcon('pi-map-marker');
        $m->persist($casaRural);

        return [$casaHome, $pisBcn, $casaRural];
    }

    // ─────────────────────────────────────────────────────────
    // MEMBRESÍAS
    // ─────────────────────────────────────────────────────────

    private function createMemberships(
        ObjectManager $m,
        Household $casaHome,
        Household $pisBcn,
        Household $casaRural,
        User $naomi,
        User $joan,
        User $anna,
        User $pau,
        User $sara
    ): void {
        $memberships = [
            // Casa Home — Naomi propietaria, Joan y Anna miembros
            [$naomi,  $casaHome,  'owner'],
            [$joan,   $casaHome,  'member'],
            [$anna,   $casaHome,  'member'],

            // Pis Barcelona — Pau propietario, Sara miembro
            [$pau,    $pisBcn,    'owner'],
            [$sara,   $pisBcn,    'member'],
            [$joan,   $pisBcn,    'member'],

            // Casa Rural — Naomi y Pau co-propietarios
            [$naomi,  $casaRural, 'owner'],
            [$pau,    $casaRural, 'member'],
        ];

        foreach ($memberships as [$user, $house, $role]) {
            $hm = new HouseholdMember();
            $hm->setUser($user);
            $hm->setHousehold($house);
            $hm->setRole($role);
            $m->persist($hm);
        }
    }

    // ─────────────────────────────────────────────────────────
    // HELPER — Crear gasto compartido
    // ─────────────────────────────────────────────────────────

    private function makeSharedExpense(
        ObjectManager $m,
        Household $house,
        User $paidBy,
        string $title,
        string $amount,
        string $category,
        array $participants,       // [User, paid bool]
        ?\DateTime $paidAt = null,
        ?string $periodicity = null,
        ?int $dayOfMonth = null,
        ?string $description = null,
        ?\DateTime $dueDate = null
    ): Expense {
        $expense = new Expense();
        $expense->setTitle($title);
        $expense->setDescription($description);
        $expense->setAmount($amount);
        $expense->setCategory($category);
        $expense->setPaymentType('shared');
        $expense->setHousehold($house);
        $expense->setPaidBy($paidBy);
        $expense->setPaidAt($paidAt);
        $expense->setPeriodicity($periodicity);
        $expense->setRecurrenceDayOfMonth($dayOfMonth);
        if ($dueDate) {
            $expense->setDueDate($dueDate);
        }
        $m->persist($expense);

        $perPerson = round((float)$amount / count($participants), 2);
        foreach ($participants as [$user, $paid]) {
            $share = new ExpenseShare();
            $share->setExpense($expense);
            $share->setUser($user);
            $share->setAmountOwed((string)$perPerson);
            $share->setIsPaid($paid);
            $share->setPaidAt($paid ? ($paidAt ?? new \DateTime()) : null);
            $m->persist($share);
        }

        return $expense;
    }

    private function makeIndividualExpense(
        ObjectManager $m,
        Household $house,
        User $paidBy,
        string $title,
        string $amount,
        string $category,
        bool $isPaid = true,
        ?\DateTime $paidAt = null,
        ?string $description = null,
        ?\DateTime $dueDate = null
    ): Expense {
        $expense = new Expense();
        $expense->setTitle($title);
        $expense->setDescription($description);
        $expense->setAmount($amount);
        $expense->setCategory($category);
        $expense->setPaymentType('individual');
        $expense->setHousehold($house);
        $expense->setPaidBy($paidBy);
        $expense->setPaidAt($paidAt);
        $expense->setIsPaid($isPaid);
        if ($dueDate) {
            $expense->setDueDate($dueDate);
        }

        // Cuota única del pagador
        $share = new ExpenseShare();
        $share->setExpense($expense);
        $share->setUser($paidBy);
        $share->setAmountOwed($amount);
        $share->setIsPaid($isPaid);
        $share->setPaidAt($isPaid ? ($paidAt ?? new \DateTime()) : null);
        $m->persist($share);

        $m->persist($expense);
        return $expense;
    }

    // ─────────────────────────────────────────────────────────
    // HELPER — Crear tarea
    // ─────────────────────────────────────────────────────────

    private function makeTask(
        ObjectManager $m,
        Household $house,
        User $assignedTo,
        string $title,
        string $priority = 'Mitja',
        bool $completed = false,
        ?string $category = null,
        ?string $description = null,
        ?\DateTime $dueDate = null
    ): Task {
        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setHousehold($house);
        $task->setAssignedTo($assignedTo);
        $task->setPriority($priority);
        $task->setCompleted($completed);
        $task->setCategory($category);
        $task->setCreatedAt(new \DateTime());
        if ($dueDate) {
            $task->setDueDate($dueDate);
        }
        $m->persist($task);
        return $task;
    }

    // ─────────────────────────────────────────────────────────
    // HELPER — Crear evento
    // ─────────────────────────────────────────────────────────

    private function makeEvent(
        ObjectManager $m,
        Household $house,
        User $createdBy,
        string $title,
        \DateTime $start,
        ?\DateTime $end = null,
        array $participants = [],
        string $color = '#ea580c',
        ?string $location = null,
        ?string $description = null,
        bool $allDay = false
    ): Event {
        $event = new Event();
        $event->setTitle($title);
        $event->setDescription($description);
        $event->setHousehold($house);
        $event->setCreatedBy($createdBy);
        $event->setStartDate($start);
        if ($end) {
            $event->setEndDate($end);
        }
        $event->setColor($color);
        $event->setLocation($location);
        $event->setIsAllDay($allDay);
        foreach ($participants as $p) {
            $event->addParticipant($p);
        }
        $m->persist($event);
        return $event;
    }

    // ─────────────────────────────────────────────────────────
    // TAREAS — CASA HOME
    // ─────────────────────────────────────────────────────────

    private function createTasksCasaHome(
        ObjectManager $m, Household $h,
        User $naomi, User $joan, User $anna
    ): void {
        // Tareas pendientes — variedad de prioridades
        $this->makeTask($m, $h, $joan,  'Sacar la basura',           'Alta',  false, 'Limpieza',    'Reciclaje incluido',            new \DateTime('+1 day'));
        $this->makeTask($m, $h, $anna,  'Fregar los platos',          'Alta',  false, 'Limpieza',    null,                            new \DateTime('today'));
        $this->makeTask($m, $h, $naomi, 'Compra semanal',             'Alta',  false, 'Compras',     'Mercadona o Lidl',              new \DateTime('+2 days'));
        $this->makeTask($m, $h, $joan,  'Limpiar el baño',            'Mitja', false, 'Limpieza',    null,                            new \DateTime('+3 days'));
        $this->makeTask($m, $h, $anna,  'Barrer el salón',            'Mitja', false, 'Limpieza',    null,                            new \DateTime('+4 days'));
        $this->makeTask($m, $h, $naomi, 'Renovar contrato internet',  'Mitja', false, 'Gestiones',   'Llamar a la operadora',         new \DateTime('+5 days'));
        $this->makeTask($m, $h, $joan,  'Regar las plantas',          'Baixa', false, 'Naturaleza',  null,                            new \DateTime('+2 days'));
        $this->makeTask($m, $h, $anna,  'Organizar armario entrada',  'Baixa', false, 'Organización',null,                            null);

        // Tareas completadas — historial de trabajo
        $this->makeTask($m, $h, $naomi, 'Pagar el alquiler de mayo',  'Alta',  true,  'Gestiones',   'Transferencia hecha ✓',         new \DateTime('-5 days'));
        $this->makeTask($m, $h, $joan,  'Cambiar bombilla cocina',    'Mitja', true,  'Mantenimiento',null,                           new \DateTime('-3 days'));
        $this->makeTask($m, $h, $anna,  'Limpiar nevera',             'Mitja', true,  'Limpieza',    null,                            new \DateTime('-2 days'));
        $this->makeTask($m, $h, $naomi, 'Comprar papel higiénico',    'Baixa', true,  'Compras',     null,                            new \DateTime('-1 day'));
    }

    // ─────────────────────────────────────────────────────────
    // TAREAS — PIS BCN
    // ─────────────────────────────────────────────────────────

    private function createTasksPisBcn(
        ObjectManager $m, Household $h,
        User $pau, User $sara
    ): void {
        $this->makeTask($m, $h, $pau,  'Hacer la compra del mes',    'Alta',  false, 'Compras',    'Lista compartida en Notion',    new \DateTime('+1 day'));
        $this->makeTask($m, $h, $sara, 'Limpiar terraza',            'Mitja', false, 'Limpieza',   null,                            new \DateTime('+3 days'));
        $this->makeTask($m, $h, $pau,  'Revisar calefacción',        'Alta',  false, 'Mantenimiento','Llama al técnico si no va',   new \DateTime('+2 days'));
        $this->makeTask($m, $h, $sara, 'Renovar seguro del hogar',   'Mitja', false, 'Gestiones',  null,                            new \DateTime('+10 days'));
        $this->makeTask($m, $h, $pau,  'Montar estanterías salón',   'Baixa', false, 'Decoración', 'IKEA KALLAX x2',               null);

        // Completadas
        $this->makeTask($m, $h, $sara, 'Pagar comunidad mayo',       'Alta',  true,  'Gestiones',  null,                            new \DateTime('-4 days'));
        $this->makeTask($m, $h, $pau,  'Cambiar filtro agua',        'Mitja', true,  'Mantenimiento',null,                          new \DateTime('-2 days'));
    }

    // ─────────────────────────────────────────────────────────
    // TAREAS — CASA RURAL
    // ─────────────────────────────────────────────────────────

    private function createTasksCasaRural(
        ObjectManager $m, Household $h,
        User $naomi, User $pau
    ): void {
        $this->makeTask($m, $h, $naomi, 'Podar el jardín',           'Mitja', false, 'Exterior',   null,                            new \DateTime('+7 days'));
        $this->makeTask($m, $h, $pau,   'Preparar barbacoa mayo',    'Alta',  false, 'Eventos',    'Comprar carbón y material',     new \DateTime('+14 days'));
        $this->makeTask($m, $h, $naomi, 'Revisar instalación solar', 'Alta',  false, 'Mantenimiento','Llamar al instalador',       new \DateTime('+5 days'));
        $this->makeTask($m, $h, $pau,   'Cortar el césped',          'Mitja', false, 'Exterior',   null,                            new \DateTime('+7 days'));

        // Completadas
        $this->makeTask($m, $h, $naomi, 'Abrir la casa para verano', 'Alta',  true,  'Gestiones',  'Ventilación y limpieza hecha ✓',new \DateTime('-7 days'));
        $this->makeTask($m, $h, $pau,   'Comprar leña',              'Baixa', true,  'Compras',    null,                            new \DateTime('-3 days'));
    }

    // ─────────────────────────────────────────────────────────
    // GASTOS — CASA HOME
    // ─────────────────────────────────────────────────────────

    private function createExpensesCasaHome(
        ObjectManager $m, Household $h,
        User $naomi, User $joan, User $anna
    ): void {
        // --- MENSUALES RECURRENTES pagados este mes ---
        $this->makeSharedExpense($m, $h, $naomi, 'Alquiler mayo',
            '900.00', 'Alquiler',
            [[$naomi, true], [$joan, true], [$anna, true]],
            new \DateTime('2026-05-01'), 'monthly', 1,
            'Alquiler mensual del piso compartido'
        );

        $this->makeSharedExpense($m, $h, $naomi, 'Fibra óptica mayo',
            '45.00', 'Suministros',
            [[$naomi, true], [$joan, false], [$anna, false]],
            new \DateTime('2026-05-05'), 'monthly', 5,
            'Factura mensual de internet'
        );

        $this->makeSharedExpense($m, $h, $joan, 'Electricidad mayo',
            '87.50', 'Suministros',
            [[$naomi, false], [$joan, true], [$anna, false]],
            new \DateTime('2026-05-10'), 'monthly', 10,
            'Factura de luz — tarifa nocturna'
        );

        $this->makeSharedExpense($m, $h, $anna, 'Agua mayo',
            '32.40', 'Suministros',
            [[$naomi, true], [$joan, false], [$anna, true]],
            new \DateTime('2026-05-08'), 'monthly', 8
        );

        $this->makeSharedExpense($m, $h, $naomi, 'Gas mayo',
            '54.20', 'Suministros',
            [[$naomi, true], [$joan, true], [$anna, false]],
            new \DateTime('2026-05-12'), 'monthly', 12
        );

        // --- GASTOS COMPARTIDOS PUNTUALES (mes actual) ---
        $this->makeSharedExpense($m, $h, $joan, 'Cena de piso — mayo',
            '78.60', 'Ocio',
            [[$naomi, true], [$joan, true], [$anna, false]],
            new \DateTime('2026-05-16'), null, null,
            'Restaurante Ca la Maria'
        );

        $this->makeSharedExpense($m, $h, $anna, 'Productos limpieza',
            '34.80', 'Compras',
            [[$naomi, true], [$joan, false], [$anna, true]],
            new \DateTime('2026-05-14'), null, null,
            'Mercadona — bolsa de limpieza'
        );

        $this->makeSharedExpense($m, $h, $naomi, 'Netflix compartido',
            '22.99', 'Ocio',
            [[$naomi, true], [$joan, false], [$anna, false]],
            new \DateTime('2026-05-01'), 'monthly', 1
        );

        // --- GASTOS INDIVIDUALES ---
        $this->makeIndividualExpense($m, $h, $naomi, 'Seguro hogar anual',
            '280.00', 'Seguros', true,
            new \DateTime('2026-05-02'),
            'Póliza renovada para el año 2026-2027'
        );

        $this->makeIndividualExpense($m, $h, $joan, 'Reparación ventana cocina',
            '65.00', 'Mantenimiento', true,
            new \DateTime('2026-05-07'),
            'Cristalería Montilivi'
        );

        // Pendiente de pago
        $this->makeIndividualExpense($m, $h, $anna, 'Silla oficina habitación',
            '149.99', 'Compras', false,
            null, 'Pendiente de llegar el pedido',
            new \DateTime('+3 days')
        );
    }

    // ─────────────────────────────────────────────────────────
    // GASTOS — PIS BCN
    // ─────────────────────────────────────────────────────────

    private function createExpensesPisBcn(
        ObjectManager $m, Household $h,
        User $pau, User $sara
    ): void {
        $joan = null; // Referencia placeholder, no disponible aquí; se omite Joan en el piso BCN por simplicidad

        $this->makeSharedExpense($m, $h, $pau, 'Alquiler mayo',
            '1200.00', 'Alquiler',
            [[$pau, true], [$sara, true]],
            new \DateTime('2026-05-01'), 'monthly', 1,
            'Alquiler piso Gràcia, Barcelona'
        );

        $this->makeSharedExpense($m, $h, $sara, 'Electricidad mayo',
            '72.30', 'Suministros',
            [[$pau, false], [$sara, true]],
            new \DateTime('2026-05-10'), 'monthly', 10
        );

        $this->makeSharedExpense($m, $h, $pau, 'Agua mayo',
            '28.10', 'Suministros',
            [[$pau, true], [$sara, false]],
            new \DateTime('2026-05-08'), 'monthly', 8
        );

        $this->makeSharedExpense($m, $h, $sara, 'Comunidad mayo',
            '95.00', 'Comunidad',
            [[$pau, true], [$sara, true]],
            new \DateTime('2026-05-05'), 'monthly', 5
        );

        $this->makeSharedExpense($m, $h, $pau, 'Compra semanal',
            '145.30', 'Alimentación',
            [[$pau, true], [$sara, false]],
            new \DateTime('2026-05-13'), null, null,
            'Mercadona semana 2 de mayo'
        );

        $this->makeSharedExpense($m, $h, $sara, 'Material limpieza',
            '42.60', 'Limpieza',
            [[$pau, false], [$sara, true]],
            new \DateTime('2026-05-15')
        );

        $this->makeIndividualExpense($m, $h, $pau, 'Suscripción Spotify',
            '9.99', 'Ocio', true,
            new \DateTime('2026-05-01'), null, null
        );

        $this->makeIndividualExpense($m, $h, $sara, 'Mesa escritorio IKEA',
            '199.00', 'Compras', false,
            null, 'Pendiente montaje',
            new \DateTime('+5 days')
        );
    }

    // ─────────────────────────────────────────────────────────
    // GASTOS — CASA RURAL
    // ─────────────────────────────────────────────────────────

    private function createExpensesCasaRural(
        ObjectManager $m, Household $h,
        User $naomi, User $pau
    ): void {
        $this->makeSharedExpense($m, $h, $naomi, 'Suministro eléctrico mayo',
            '38.90', 'Suministros',
            [[$naomi, true], [$pau, false]],
            new \DateTime('2026-05-10'), 'monthly', 10
        );

        $this->makeSharedExpense($m, $h, $pau, 'Propano mayo',
            '48.00', 'Suministros',
            [[$naomi, false], [$pau, true]],
            new \DateTime('2026-05-12')
        );

        $this->makeSharedExpense($m, $h, $naomi, 'Seguro rural anual',
            '420.00', 'Seguros',
            [[$naomi, true], [$pau, true]],
            new \DateTime('2026-05-03'), null, null,
            'Seguro multirriesgo para casa de campo'
        );

        $this->makeIndividualExpense($m, $h, $pau, 'Manguera jardín',
            '35.50', 'Exterior', true,
            new \DateTime('2026-05-08')
        );

        $this->makeIndividualExpense($m, $h, $naomi, 'Pintura fachada',
            '180.00', 'Mantenimiento', false,
            null, 'Pendiente de fecha para el trabajo',
            new \DateTime('+20 days')
        );
    }

    // ─────────────────────────────────────────────────────────
    // EVENTOS — CASA HOME
    // ─────────────────────────────────────────────────────────

    private function createEventsCasaHome(
        ObjectManager $m, Household $h,
        User $naomi, User $joan, User $anna
    ): void {
        // Pasado — historial
        $this->makeEvent($m, $h, $naomi, 'Bienvenida al piso',
            new \DateTime('-3 months 18:00'), new \DateTime('-3 months 22:00'),
            [$naomi, $joan, $anna], '#10b981', 'Salón casa',
            'Primera noche juntos como compañeros de piso'
        );

        $this->makeEvent($m, $h, $joan, 'Revisión anual gas',
            new \DateTime('-2 weeks 10:00'), new \DateTime('-2 weeks 11:00'),
            [$naomi, $joan], '#f59e0b', 'Casa Home',
            'Técnico de la empresa distribuidora'
        );

        // Hoy
        $this->makeEvent($m, $h, $anna, 'Reunión de piso — mayo',
            new \DateTime('today 20:30'), new \DateTime('today 21:30'),
            [$naomi, $joan, $anna], '#ea580c', 'Salón',
            'Repaso de gastos del mes y planificación de junio'
        );

        // Próximos — esta semana
        $this->makeEvent($m, $h, $joan, 'Cena con amigos en casa',
            new \DateTime('+2 days 21:00'), new \DateTime('+2 days 23:30'),
            [$naomi, $joan, $anna], '#8b5cf6', 'Casa Home',
            'Hacemos paella entre todos'
        );

        $this->makeEvent($m, $h, $naomi, 'Visita del fontanero',
            new \DateTime('+3 days 09:00'), new \DateTime('+3 days 10:00'),
            [$naomi], '#ef4444', 'Baño principal',
            'Revisar la presión del agua y grifo cocina'
        );

        // Próximo mes
        $this->makeEvent($m, $h, $naomi, 'Barbacoa de verano',
            new \DateTime('+2 weeks Saturday 13:00'), new \DateTime('+2 weeks Saturday 20:00'),
            [$naomi, $joan, $anna], '#f97316', 'Terraza',
            'Primera barbacoa de la temporada'
        );

        $this->makeEvent($m, $h, $anna, 'Vacaciones — salida',
            new \DateTime('+3 weeks Monday 08:00'), null,
            [$naomi, $joan, $anna], '#06b6d4', null,
            'Inicio de vacaciones de verano', true
        );

        // Evento todo-el-día
        $this->makeEvent($m, $h, $naomi, 'Día de la comunidad',
            new \DateTime('+5 days'), null,
            [$naomi, $joan, $anna], '#84cc16', 'Portal del edificio',
            'Junta de vecinos anual', true
        );
    }

    // ─────────────────────────────────────────────────────────
    // EVENTOS — PIS BCN
    // ─────────────────────────────────────────────────────────

    private function createEventsPisBcn(
        ObjectManager $m, Household $h,
        User $pau, User $sara
    ): void {
        $this->makeEvent($m, $h, $pau, 'Junta de vecinos',
            new \DateTime('+4 days 19:00'), new \DateTime('+4 days 20:30'),
            [$pau, $sara], '#f59e0b', 'Sala comunidad',
            'Votación mejoras ascensor'
        );

        $this->makeEvent($m, $h, $sara, 'Inspección técnica piso',
            new \DateTime('+1 week 10:00'), new \DateTime('+1 week 11:30'),
            [$pau, $sara], '#ef4444', 'Piso Gràcia',
            'Inspección anual de la propiedad'
        );

        $this->makeEvent($m, $h, $pau, 'Brunch sabado',
            new \DateTime('next Saturday 11:00'), new \DateTime('next Saturday 14:00'),
            [$pau, $sara], '#10b981', 'Cocina',
            'Brunch especial — traer croissants'
        );

        $this->makeEvent($m, $h, $sara, 'Mudanza estanterías',
            new \DateTime('+6 days 09:00'), new \DateTime('+6 days 13:00'),
            [$pau, $sara], '#6366f1', 'Salón',
            'Montaje de las estanterías KALLAX'
        );
    }

    // ─────────────────────────────────────────────────────────
    // EVENTOS — CASA RURAL
    // ─────────────────────────────────────────────────────────

    private function createEventsCasaRural(
        ObjectManager $m, Household $h,
        User $naomi, User $pau
    ): void {
        $this->makeEvent($m, $h, $naomi, 'Fin de semana rural — mayo',
            new \DateTime('next Friday 17:00'), new \DateTime('next Sunday 18:00'),
            [$naomi, $pau], '#84cc16', 'Casa Rural Montilivi',
            'Primer fin de semana de la temporada'
        );

        $this->makeEvent($m, $h, $pau, 'Técnico placas solares',
            new \DateTime('+8 days 09:30'), new \DateTime('+8 days 11:00'),
            [$naomi, $pau], '#f97316', 'Azotea casa rural',
            'Revisión y limpieza anual de las placas'
        );

        $this->makeEvent($m, $h, $naomi, 'Barbacoa de junio',
            new \DateTime('+3 weeks Saturday 13:00'), new \DateTime('+3 weeks Saturday 21:00'),
            [$naomi, $pau], '#ea580c', 'Jardín casa rural',
            'Evento familiar — confirmar asistentes'
        );
    }

    // ─────────────────────────────────────────────────────────
    // NOTIFICACIONES
    // ─────────────────────────────────────────────────────────

    private function createNotifications(
        ObjectManager $m,
        User $naomi, User $joan, User $anna, User $pau, User $sara,
        Household $casaHome, Household $pisBcn, Household $casaRural
    ): void {
        // [user, household, title, message, type, priority, route]
        $notifications = [
            [$naomi, $casaHome,  'Fibra pendiente de pago',    'Joan y Anna aún no han pagado su parte de internet de mayo.',  'expense', 'high',   '/expenses'],
            [$naomi, $casaHome,  'Reunión de piso esta noche', 'Recuerda: reunión de piso hoy a las 20:30.',                   'event',   'medium', '/calendar'],
            [$joan,  $casaHome,  'Toca sacar la basura',       'Hoy es tu turno de sacar el reciclaje.',                       'task',    'high',   '/tasks'],
            [$anna,  $casaHome,  'Platos pendientes',          'No olvides fregar los platos asignados para hoy.',              'task',    'medium', '/tasks'],
            [$pau,   $pisBcn,    'Alquiler pagado ✓',          'El alquiler de mayo ha sido pagado correctamente.',             'expense', 'low',    '/expenses'],
            [$sara,  $pisBcn,    'Mesa escritorio pendiente',  'La mesa escritorio aún no está marcada como pagada.',           'expense', 'medium', '/expenses'],
            [$naomi, $casaRural, 'Barbacoa confirmada',        'La barbacoa de verano está en el calendario.',                  'event',   'low',    '/calendar'],
            [$joan,  $casaHome,  'Electricidad mayo pendiente','Anna aún no ha pagado su parte de la luz.',                     'expense', 'high',   '/expenses'],
            [$pau,   $pisBcn,    'Inspección piso la semana que viene', 'Preparar el acceso y documentación.',                  'event',   'medium', '/calendar'],
            [$naomi, $casaHome,  'Visita fontanero mañana',   'Revisa que estás en casa a las 9:00.',                           'task',    'high',   '/calendar'],
        ];

        foreach ($notifications as $i => [$user, $household, $title, $message, $type, $priority, $route]) {
            $n = new Notification();
            $n->setUser($user);
            $n->setHousehold($household);
            $n->setTitle($title);
            $n->setMessage($message);
            $n->setType($type);
            $n->setPriority($priority);
            $n->setRoute($route);
            $n->setNotificationKey('demo-notif-' . ($i + 1) . '-' . $user->getEmail());
            $n->setIsRead(false);
            $n->setUpdatedAt(new \DateTime());
            $m->persist($n);
        }
    }
}
