<?php

namespace App\Controller\twig;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/task')]
#[IsGranted('ROLE_USER')] // Només els usuaris logejats podràn accedir a aquest controlador!
final class TaskController extends AbstractController
{
   /** Lista tareas de la casa del usuario. */
   #[Route('/', name: 'app_task_index', methods: ['GET'])]
public function index(TaskRepository $taskRepository): Response
{
    // Obtenir l'usuari actual
    /** @var \App\Entity\User $user */
    $user = $this->getUser();
    if (!$user instanceof \App\Entity\User) {
        throw $this->createAccessDeniedException(); //retorna null si algú accedeix sense login
}
    
    // Només mostrar tasques de la seva llar
    $tasks = $taskRepository->findBy([
        'household' => $user->getHousehold(),
        'isActive' => true,
    ]);

    return $this->render('task/index.html.twig', [
        'tasks' => $tasks,
    ]);
}

    /** Crea una tarea asignandola a la casa y usuario actual. */
    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();

        // Assignar automàticament la llar i usuari actual
        /** 
         * L'usuari no ha de triar manualment la llar (ja sabem quina és!)
         * La tasca s'assigna automàticament a l'usuari que la crea
         * Evita que algú pugui falsificar aquestes dades
         * 
         * **/

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
    
        $task->setHousehold($user->getHousehold());
        $task->setAssignedTo($user);
        $task->setCreatedAt(new \DateTime());
        $task->setCompleted(false);


        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    /** Muestra el detalle de una tarea. */
    #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    /** Edita una tarea desde el formulario clasico. */
    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    /** Elimina una tarea validando CSRF. */
    #[Route('/{id}', name: 'app_task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->getPayload()->getString('_token'))) {
            $task->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_task_index', [], Response::HTTP_SEE_OTHER);
    }
}
