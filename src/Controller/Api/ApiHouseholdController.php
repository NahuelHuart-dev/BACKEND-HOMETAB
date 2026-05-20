<?php

namespace App\Controller\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Service\SafeImageStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para gestionar los hogares.
 * Permite listar, crear, unirse, editar y gestionar miembros de los hogares.
 */
#[Route('/api/households')]
#[IsGranted('ROLE_USER')]
class ApiHouseholdController extends AbstractController
{
    private const MANAGER_ROLES = ['owner', 'admin'];

    // 1. LISTAR MIS CASAS
    /**
     * Obtiene la lista de hogares a los que pertenece el usuario.
     * 
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON con la lista de hogares.
     */
    #[Route('', name: 'api_households_list', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $householdsData = [];

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $allHouseholds = $em->getRepository(Household::class)->findAll();
            foreach ($allHouseholds as $household) {
                $householdsData[] = $this->mapHousehold($household, 'SUPER_ADMIN');
            }
        } else {
            $memberships = $user->getHouseholdMemberships()->toArray();
            usort($memberships, fn (HouseholdMember $a, HouseholdMember $b) => $a->getSortOrder() <=> $b->getSortOrder());
            foreach ($memberships as $membership) {
                $household = $membership->getHousehold();
                $householdsData[] = $this->mapHousehold($household, $membership->getRole(), $membership->getSortOrder());
            }
        }

        return $this->json($householdsData);
    }

    // 2. CREAR NUEVA CASA (Genera código secreto)
    /**
     * Crea un nuevo hogar, generando un código secreto de invitación.
     * 
     * @param Request $request Petición HTTP con los datos del nuevo hogar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param SafeImageStorage $imageStorage Servicio de almacenamiento de imágenes.
     * @return JsonResponse Respuesta JSON confirmando la creación y devolviendo el hogar.
     */
    #[Route('/new', name: 'api_households_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, SafeImageStorage $imageStorage): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'El nombre de la casa es obligatorio.'], 400);
        }

        $household = new Household();
        $household->setName($data['name']);
        $household->setAvatarIcon($data['avatarIcon'] ?? 'pi-home');

        if (!empty($data['avatarCropData'])) {
            $avatarPath = $imageStorage->storeDataUrl($data['avatarCropData'], 'households');
            if ($avatarPath === null) {
                return $this->json(['error' => 'No se pudo procesar la imagen de la casa.'], 400);
            }
            $household->setAvatar($avatarPath);
        }
        
        // Generamos un código aleatorio de 6 caracteres (Ej: 8F2A9B)
        $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $household->setInviteCode($code);
        
        $em->persist($household); 

        $membership = new HouseholdMember();
        $membership->setUser($user);
        $membership->setHousehold($household);
        $membership->setRole('owner');
        $membership->setSortOrder($this->nextSortOrder($user, $em));

        $em->persist($membership);
        $em->flush();

        return $this->json([
            'message' => 'Casa creada correctamente.',
            'household' => $this->mapHousehold($household, 'owner')
        ], 201);
    }

    // 3. UNIRSE A UNA CASA CON CÓDIGO
    /**
     * Permite a un usuario unirse a un hogar usando un código de invitación.
     * 
     * @param Request $request Petición HTTP con el código de invitación.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON indicando éxito o error.
     */
    #[Route('/join', name: 'api_households_join', methods: ['POST'])]
    public function join(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (empty($data['code'])) {
            return $this->json(['error' => 'El código es obligatorio.'], 400);
        }

        $code = strtoupper(trim($data['code']));
        $household = $em->getRepository(Household::class)->findOneBy(['inviteCode' => $code]);

        if (!$household) return $this->json(['error' => 'Código de invitación inválido.'], 404);

        $existingMembership = $em->getRepository(HouseholdMember::class)->findOneBy([
            'user' => $user,
            'household' => $household
        ]);

        if ($existingMembership) return $this->json(['error' => 'Ya eres miembro de esta casa.'], 409);

        $membership = new HouseholdMember();
        $membership->setUser($user);
        $membership->setHousehold($household);
        $membership->setRole('member');
        $membership->setSortOrder($this->nextSortOrder($user, $em));

        $em->persist($membership);
        $em->flush();

        return $this->json(['message' => 'Te has unido a ' . $household->getName() . ' correctamente.'], 200);
    }

    /**
     * Actualiza el orden de los hogares para el usuario.
     * 
     * @param Request $request Petición HTTP con el nuevo orden de los IDs.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON confirmando la actualización del orden.
     */
    #[Route('/order', name: 'api_households_order', methods: ['PUT'])]
    public function updateOrder(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];
        $orderedIds = $data['orderedIds'] ?? [];

        if (!is_array($orderedIds)) {
            return $this->json(['error' => 'El orden enviado no es valido.'], 400);
        }

        $memberships = $em->getRepository(HouseholdMember::class)->findBy(['user' => $user]);
        $membershipsByHouseholdId = [];
        foreach ($memberships as $membership) {
            $membershipsByHouseholdId[$membership->getHousehold()?->getId()] = $membership;
        }

        foreach (array_values($orderedIds) as $index => $householdId) {
            $id = (int) $householdId;
            if (!isset($membershipsByHouseholdId[$id])) {
                return $this->json(['error' => 'No puedes ordenar una casa donde no eres miembro.'], 403);
            }
            $membershipsByHouseholdId[$id]->setSortOrder($index + 1);
        }

        $em->flush();

        return $this->json([
            'message' => 'Orden actualizado correctamente.',
            'orderedIds' => array_map('intval', $orderedIds),
        ]);
    }

    // 4. VER MIEMBROS DE UNA CASA
    /**
     * Obtiene los miembros de un hogar específico.
     * 
     * @param int $id ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON con la lista de miembros y detalles del hogar.
     */
    #[Route('/{id}/members', name: 'api_households_members', methods: ['GET'])]
    public function getMembers(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $em->getRepository(Household::class)->find($id);
        if (!$household) return $this->json(['error' => 'Casa no encontrada'], 404);
        $currentMembership = $this->findMembership($user, $household, $em);
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$currentMembership) {
            return $this->json(['error' => 'No perteneces a esta casa.'], 403);
        }

        $membersData = [];
        foreach ($household->getMembers() as $membership) {
            $memberUser = $membership->getUser();
            $membersData[] = [
                'id' => $memberUser->getId(),
                'firstName' => $memberUser->getFirstName(),
                'lastName' => $memberUser->getLastName(),
                'email' => $memberUser->getEmail(),
                'role' => $membership->getRole(),
                'avatar' => $memberUser->getAvatar(),
                'avatarIcon' => $memberUser->getAvatarIcon(),
            ];
        }

        return $this->json([
            'household' => $this->mapHousehold($household, $currentMembership?->getRole() ?? 'SUPER_ADMIN'),
            'inviteCode' => $household->getInviteCode(),
            'members' => $membersData
        ]);
    }

    // 5. ACTUALIZAR AJUSTES VISUALES DE UNA CASA
    /**
     * Actualiza los ajustes y datos de un hogar.
     * 
     * @param int $id ID del hogar.
     * @param Request $request Petición HTTP con los nuevos datos.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @param SafeImageStorage $imageStorage Servicio de almacenamiento de imágenes.
     * @return JsonResponse Respuesta JSON confirmando la actualización.
     */
    #[Route('/{id}', name: 'api_households_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SafeImageStorage $imageStorage
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $household = $em->getRepository(Household::class)->find($id);

        if (!$household) return $this->json(['error' => 'Casa no encontrada'], 404);
        if (!$this->canManageHousehold($user, $household, $em)) {
            return $this->json(['error' => 'No tienes permisos para editar esta casa.'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['name'])) {
            $name = trim((string) $data['name']);
            if ($name === '') {
                return $this->json(['error' => 'El nombre de la casa es obligatorio.'], 400);
            }
            $household->setName($name);
        }

        if (isset($data['avatarIcon']) && !$household->getAvatar()) {
            $household->setAvatarIcon($data['avatarIcon']);
        }

        if (!empty($data['avatarCropData'])) {
            $avatarPath = $imageStorage->storeDataUrl($data['avatarCropData'], 'households');
            if ($avatarPath === null) {
                return $this->json(['error' => 'No se pudo procesar la imagen de la casa.'], 400);
            }
            $imageStorage->deletePublicFile($household->getAvatar(), 'households');
            $household->setAvatar($avatarPath);
        }

        if (!empty($data['removeAvatar'])) {
            $imageStorage->deletePublicFile($household->getAvatar(), 'households');
            $household->setAvatar(null);
            if (isset($data['avatarIcon'])) {
                $household->setAvatarIcon($data['avatarIcon']);
            }
        }

        $em->flush();

        $membership = $this->findMembership($user, $household, $em);

        return $this->json([
            'message' => 'Casa actualizada correctamente.',
            'household' => $this->mapHousehold($household, $membership?->getRole() ?? 'SUPER_ADMIN'),
        ]);
    }

    // 5. EXPULSAR A UN MIEMBRO
    /**
     * Expulsa a un miembro de un hogar.
     * 
     * @param int $id ID del hogar.
     * @param int $userId ID del usuario a expulsar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON confirmando la expulsión.
     */
    #[Route('/{id}/members/{userId}', name: 'api_households_remove_member', methods: ['DELETE'])]
    public function removeMember(int $id, int $userId, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $em->getRepository(Household::class)->find($id);
        if (!$household) return $this->json(['error' => 'Casa no encontrada'], 404);

        if (!$this->canManageHousehold($user, $household, $em)) {
            return $this->json(['error' => 'No tienes permisos para expulsar miembros.'], 403);
        }

        $membership = $em->getRepository(HouseholdMember::class)->findOneBy([
            'household' => $household,
            'user' => $userId
        ]);

        if (!$membership) return $this->json(['error' => 'El usuario no es miembro de esta casa.'], 404);

        if ($membership->getRole() === 'owner') {
            return $this->json(['error' => 'No puedes expulsar al propietario de la casa.'], 403);
        }

        $em->remove($membership);
        $em->flush();

        return $this->json(['message' => 'Miembro expulsado correctamente.']);
    }
    // 6. PERSONALIZAR CÓDIGO DE INVITACIÓN
    /**
     * Personaliza el código de invitación de un hogar.
     * 
     * @param int $id ID del hogar.
     * @param Request $request Petición HTTP con el nuevo código.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return JsonResponse Respuesta JSON confirmando el cambio del código.
     */
    #[Route('/{id}/invite-code', name: 'api_households_update_code', methods: ['PUT'])]
    public function updateInviteCode(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $em->getRepository(Household::class)->find($id);

        if (!$household) return $this->json(['error' => 'Casa no encontrada'], 404);

        // Verificamos que sea el OWNER o SUPER_ADMIN
        $isOwner = false;
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $isOwner = true;
        } else {
            $membership = $em->getRepository(HouseholdMember::class)->findOneBy(['user' => $user, 'household' => $household]);
            if ($membership && $membership->getRole() === 'owner') {
                $isOwner = true;
            }
        }

        if (!$isOwner) return $this->json(['error' => 'Solo el propietario puede cambiar el código.'], 403);

        $data = json_decode($request->getContent(), true);
        if (empty($data['code'])) return $this->json(['error' => 'El código no puede estar vacío.'], 400);

        // Limpiamos el código (mayúsculas y sin espacios)
        $newCode = strtoupper(trim($data['code']));

        // Comprobamos si el código ya existe en OTRA casa
        $existingHouse = $em->getRepository(Household::class)->findOneBy(['inviteCode' => $newCode]);
        if ($existingHouse && $existingHouse->getId() !== $household->getId()) {
            return $this->json(['error' => 'Este código ya está en uso por otra casa. Elige otro.'], 409);
        }

        $household->setInviteCode($newCode);
        $em->flush();

        return $this->json(['message' => 'Código actualizado correctamente.', 'inviteCode' => $newCode]);
    }

    private function mapHousehold(Household $household, string $role, int $sortOrder = 0): array
    {
        return [
            'id' => $household->getId(),
            'name' => $household->getName(),
            'role' => $role,
            'inviteCode' => $household->getInviteCode(),
            'avatar' => $household->getAvatar(),
            'avatarIcon' => $household->getAvatarIcon(),
            'sortOrder' => $sortOrder,
        ];
    }

    private function canManageHousehold(User $user, Household $household, EntityManagerInterface $em): bool
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $membership = $this->findMembership($user, $household, $em);

        return $membership && in_array($membership->getRole(), self::MANAGER_ROLES, true);
    }

    private function findMembership(User $user, Household $household, EntityManagerInterface $em): ?HouseholdMember
    {
        return $em->getRepository(HouseholdMember::class)->findOneBy([
            'user' => $user,
            'household' => $household,
        ]);
    }

    private function nextSortOrder(User $user, EntityManagerInterface $em): int
    {
        $memberships = $em->getRepository(HouseholdMember::class)->findBy(['user' => $user]);
        $maxSortOrder = 0;
        foreach ($memberships as $membership) {
            $maxSortOrder = max($maxSortOrder, $membership->getSortOrder());
        }

        return $maxSortOrder + 1;
    }
}
