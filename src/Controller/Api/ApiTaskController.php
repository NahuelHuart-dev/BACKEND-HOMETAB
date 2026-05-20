<?php

namespace App\Controller\Api;

use App\Entity\Household;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controlador de API para la gestión de tareas de un hogar.
 * Permite listar, crear, editar y eliminar tareas.
 */
#[Route('/api/households/{homeId}/tasks')]
#[IsGranted('ROLE_USER')]
class ApiTaskController extends AbstractController
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

    // --- EL MAPEO MÁGICO PARA EVITAR EL ERROR 500 ---
    private function mapTaskToArray(Task $task): array
    {
        $assignedTo = $task->getAssignedTo();
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'completed' => $task->isCompleted(),
            'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
            // Cuidado aquí: verificamos si los métodos existen por si no actualizaste la entidad
            'priority' => method_exists($task, 'getPriority') ? $task->getPriority() : 'Mitja',
            'periodicity' => method_exists($task, 'getPeriodicity') ? $task->getPeriodicity() : null,
            'category' => method_exists($task, 'getCategory') ? $task->getCategory() : null,
            'assignedTo' => $assignedTo ? [
                'id' => $assignedTo->getId(),
                'firstName' => $assignedTo->getFirstName(),
                'lastName' => $assignedTo->getLastName()
            ] : null
        ];
    }

    /**
     * Obtiene la lista de tareas activas para un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON con la lista de tareas.
     */
    #[Route('', name: 'api_task_index', methods: ['GET'])]
    public function index(int $homeId, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $household = $this->getValidHousehold($homeId, $user, $em);

        if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

        $tasks = $em->getRepository(Task::class)->findBy(['household' => $household, 'isActive' => true]);
        $data = array_map([$this, 'mapTaskToArray'], $tasks);

        return $this->json($data, 200);
    }

    /**
     * Crea una nueva tarea en un hogar específico.
     * 
     * @param int $homeId ID del hogar.
     * @param Request $request Petición HTTP con los datos de la tarea.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando la creación y los datos de la tarea.
     */
    #[Route('', name: 'api_task_new', methods: ['POST'])]
    public function new(int $homeId, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);

            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $data = json_decode($request->getContent(), true);

            if (empty($data['title'])) {
                return $this->json(['error' => 'El títol és obligatori'], 400);
            }

            $task = new Task();
            $task->setTitle($data['title']);
            $task->setDescription($data['description'] ?? '');
            $task->setHousehold($household);
            $task->setCreatedAt(new \DateTime());
            $task->setCompleted(false);

            // GESTIÓN DEL ASIGNADO (A dedo o Ruleta)
            if (!empty($data['assignedTo'])) {
                $assignedUser = $em->getRepository(User::class)->find($data['assignedTo']);
                $task->setAssignedTo($assignedUser ?: $user);
            } else {
                $task->setAssignedTo($user); // Por defecto, el que la crea
            }

            if (!empty($data['dueDate'])) {
                $task->setDueDate(new \DateTime($data['dueDate']));
            }

            if (method_exists($task, 'setPriority')) $task->setPriority($data['priority'] ?? 'Mitja');
            if (method_exists($task, 'setPeriodicity')) $task->setPeriodicity($data['periodicity'] ?? 'Única');
            if (method_exists($task, 'setCategory')) $task->setCategory($data['category'] ?? null);

            $em->persist($task);
            $em->flush();

            return $this->json([
                'message' => 'Tasca creada correctament', 
                'task' => $this->mapTaskToArray($task)
            ], 201);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Edita una tarea existente en un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID de la tarea a editar.
     * @param Request $request Petición HTTP con los datos a actualizar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando la actualización de la tarea.
     */
    #[Route('/{id}', name: 'api_task_edit', methods: ['PUT', 'PATCH'])]
    public function edit(int $homeId, int $id, Request $request, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $task = $em->getRepository(Task::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$task) return $this->json(['error' => 'Tasca no trobada'], 404);

            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) $task->setTitle($data['title']);
            if (isset($data['description'])) $task->setDescription($data['description']);
            if (isset($data['completed'])) $task->setCompleted((bool) $data['completed']);
            
            if (!empty($data['assignedTo'])) {
                $assignedUser = $em->getRepository(User::class)->find($data['assignedTo']);
                if ($assignedUser) $task->setAssignedTo($assignedUser);
            }

            if (array_key_exists('dueDate', $data)) {
                $task->setDueDate(empty($data['dueDate']) ? null : new \DateTime($data['dueDate']));
            }

            if (isset($data['priority']) && method_exists($task, 'setPriority')) $task->setPriority($data['priority']);
            if (isset($data['periodicity']) && method_exists($task, 'setPeriodicity')) $task->setPeriodicity($data['periodicity']);
            if (array_key_exists('category', $data) && method_exists($task, 'setCategory')) $task->setCategory($data['category']);

            $em->flush();

            return $this->json([
                'message' => 'Tasca actualitzada', 
                'task' => $this->mapTaskToArray($task)
            ], 200);
            
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina (da de baja lógica) una tarea de un hogar.
     * 
     * @param int $homeId ID del hogar.
     * @param int $id ID de la tarea a eliminar.
     * @param EntityManagerInterface $em Manejador de entidades.
     * @return Response Respuesta JSON confirmando la eliminación de la tarea.
     */
    #[Route('/{id}', name: 'api_task_delete', methods: ['DELETE'])]
    public function delete(int $homeId, int $id, EntityManagerInterface $em): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $household = $this->getValidHousehold($homeId, $user, $em);
            if (!$household) return $this->json(['error' => 'Accés denegat'], 403);

            $task = $em->getRepository(Task::class)->findOneBy(['id' => $id, 'household' => $household]);
            if (!$task) return $this->json(['error' => 'Tasca no trobada'], 404);

            $task->setIsActive(false);
            $em->flush();

            return $this->json(['message' => 'Tasca donada de baixa']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }
}
