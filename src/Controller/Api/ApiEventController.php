<?php

namespace App\Controller\Api;

use App\Entity\Event;
use App\Entity\Household;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para la gestión de eventos en un hogar.
 * Proporciona endpoints para listar, crear, editar y eliminar eventos.
 */
#[Route('/api/households/{homeId}/events')]
#[IsGranted('ROLE_USER')]
class ApiEventController extends AbstractController
{
    private function getValidHousehold(int $homeId, User $user, EntityManagerInterface $em): ?Household
    {
        $household = $em->getRepository(Household::class)->find($homeId);
        if (!$household) return null;

        if ($this->isGranted('ROLE_SUPER_ADMIN')) return $household;

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

    // --- LA FUNCIÓN MÁGICA QUE FALTABA ---
    private function mapEventToArray(Event $event): array
    {
        return [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'startDate' => $event->getStartDate() ? $event->getStartDate()->format('c') : null,
            'endDate' => method_exists($event, 'getEndDate') && $event->getEndDate() ? $event->getEndDate()->format('c') : null,
            'location' => method_exists($event, 'getLocation') ? $event->getLocation() : null,
            'color' => method_exists($event, 'getColor') ? $event->getColor() : '#3b82f6',
            'isAllDay' => method_exists($event, 'isAllDay') ? $event->isAllDay() : false,
            'createdBy' => $event->getCreatedBy() ? [
                'id' => $event->getCreatedBy()->getId(),
                'name' => $event->getCreatedBy()->getFullName(),
                'email' => $event->getCreatedBy()->getEmail(),
            ] : null,
            'participants' => array_map(static fn (User $participant) => [
                'id' => $participant->getId(),
                'name' => $participant->getFullName(),
                'email' => $participant->getEmail(),
            ], $event->getParticipants()->toArray()),
        ];
    }

    /**
     * Obtiene la lista de eventos activos para un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON con la lista de eventos o un mensaje de error.
     */
    #[Route('', name: 'api_event_index', methods: ['GET'])]
    public function index(int $homeId, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $this->getValidHousehold($homeId, $user, $em);

        if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

        $events = $em->getRepository(Event::class)->findBy(['household' => $household, 'isActive' => true]);
        
        // Mapeamos los datos para enviarlos perfectos a Vue
        $data = array_map([$this, 'mapEventToArray'], $events);

        return $this->json($data, 200);
    }

    /**
     * Crea un nuevo evento en un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con los datos del evento.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON indicando el éxito o fallo de la creación y los datos del evento.
     */
    #[Route('', name: 'api_event_new', methods: ['POST'])]
    public function new(int $homeId, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $data = json_decode($request->getContent(), true);

            if (empty($data['title']) || empty($data['startDate'])) {
                return $this->json(['error' => 'Títol i data d\'inici són obligatoris'], 400);
            }

            $event = new Event();
            $event->setTitle($data['title']);
            $event->setHousehold($household);
            $event->setCreatedBy($user);
            $event->setStartDate(new \DateTime($data['startDate']));
            
            if (!empty($data['endDate']) && method_exists($event, 'setEndDate')) {
                $event->setEndDate(new \DateTime($data['endDate']));
            }
            
            if (method_exists($event, 'setLocation')) $event->setLocation($data['location'] ?? null);
            if (method_exists($event, 'setColor')) $event->setColor($data['color'] ?? '#3b82f6');
            if (method_exists($event, 'setIsAllDay')) $event->setIsAllDay($data['isAllDay'] ?? false);
            if (!empty($data['description'])) $event->setDescription($data['description']);

            if (!empty($data['participants']) && is_array($data['participants'])) {
                foreach ($data['participants'] as $participantId) {
                    $participant = $em->getRepository(User::class)->find((int) $participantId);
                    if (!$participant || !$this->userBelongsToHousehold($participant, $household)) {
                        return $this->json(['error' => 'Participant invàlid'], 400);
                    }
                    $event->addParticipant($participant);
                }
            }

            $em->persist($event);
            $em->flush();

            return $this->json([
                'message' => 'Esdeveniment creat correctament', 
                'event' => $this->mapEventToArray($event)
            ], 201);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edita un evento existente de un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del evento a editar.
     * @param Request $request Petición HTTP con los datos actualizados del evento.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON indicando el resultado de la actualización y los datos del evento.
     */
    #[Route('/{id}', name: 'api_event_edit', methods: ['PUT', 'PATCH'])]
    public function edit(int $homeId, int $id, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $event = $em->getRepository(Event::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$event) return $this->json(['error' => 'Esdeveniment no trobat'], 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) $event->setTitle($data['title']);
            if (isset($data['startDate'])) $event->setStartDate(new \DateTime($data['startDate']));
            
            if (array_key_exists('endDate', $data) && method_exists($event, 'setEndDate')) {
                $event->setEndDate(empty($data['endDate']) ? null : new \DateTime($data['endDate']));
            }
            
            if (array_key_exists('location', $data) && method_exists($event, 'setLocation')) {
                $event->setLocation($data['location']);
            }
            if (array_key_exists('description', $data)) {
                $event->setDescription($data['description']);
            }
            if (array_key_exists('color', $data)) {
                $event->setColor($data['color']);
            }
            if (array_key_exists('isAllDay', $data)) {
                $event->setIsAllDay((bool) $data['isAllDay']);
            }
            if (array_key_exists('participants', $data) && is_array($data['participants'])) {
                foreach ($event->getParticipants()->toArray() as $participant) {
                    $event->removeParticipant($participant);
                }
                foreach ($data['participants'] as $participantId) {
                    $participant = $em->getRepository(User::class)->find((int) $participantId);
                    if (!$participant || !$this->userBelongsToHousehold($participant, $household)) {
                        return $this->json(['error' => 'Participant invàlid'], 400);
                    }
                    $event->addParticipant($participant);
                }
            }

            $em->flush();

            return $this->json([
                'message' => 'Esdeveniment actualitzat', 
                'event' => $this->mapEventToArray($event)
            ], 200);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina (da de baja lógica) un evento de un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID del evento a eliminar.
     * @param EntityManagerInterface $em Manejador de entidades de Doctrine.
     * @return Response Respuesta JSON confirmando la eliminación o un mensaje de error.
     */
    #[Route('/{id}', name: 'api_event_delete', methods: ['DELETE'])]
    public function delete(int $homeId, int $id, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $event = $em->getRepository(Event::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$event) return $this->json(['error' => 'Esdeveniment no trobat'], 404);

            $event->setIsActive(false);
            $em->flush();

            return $this->json(['message' => 'Esdeveniment donat de baixa']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }
}
