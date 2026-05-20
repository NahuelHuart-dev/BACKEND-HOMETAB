<?php

namespace App\Controller\twig;

use App\Entity\Expense;
use App\Entity\ExpenseShare;
use App\Entity\User;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expense')]
final class ExpenseController extends AbstractController
{
    /** Lista gastos de la casa del usuario en la vista clasica. */
    #[Route(name: 'app_expense_index', methods: ['GET'])]
    public function index(ExpenseRepository $expenseRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('expense/index.html.twig', [
            'expenses' => $expenseRepository->findBy(['household' => $user->getHousehold(), 'isActive' => true]),
        ]);
    }

    /** Crea un gasto clasico y sus cuotas iniciales. */
    #[Route('/new', name: 'app_expense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $expense = new Expense();
        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $expense->setHousehold($user->getHousehold());

            // Handle split users
            $splitUsers = $form->get('splitUsers')->getData();
            $totalPeople = count($splitUsers);
            
            if ($totalPeople > 0) {
                $amountPerPerson = $expense->getAmountPerPerson($totalPeople);
                foreach ($splitUsers as $splitUser) {
                    $share = new ExpenseShare();
                    $share->setExpense($expense);
                    $share->setUser($splitUser);
                    $share->setAmountOwed((string) $amountPerPerson);
                    $share->setIsPaid(false);
                    
                    $entityManager->persist($share);
                }
            }

            $entityManager->persist($expense);
            $entityManager->flush();

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('expense/new.html.twig', [
            'expense' => $expense,
            'form' => $form,
        ]);
    }

    /** Muestra un gasto solo si pertenece a la casa del usuario. */
    #[Route('/{id}', name: 'app_expense_show', methods: ['GET'])]
    public function show(Expense $expense): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($expense->getHousehold() !== $user->getHousehold()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('expense/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    /** Edita un gasto solo si pertenece a la casa del usuario. */
    #[Route('/{id}/edit', name: 'app_expense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($expense->getHousehold() !== $user->getHousehold()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ExpenseType::class, $expense);
        
        // We won't try to populate splitUsers here to keep it simple, 
        // normally we would map existing shares back to users.
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('expense/edit.html.twig', [
            'expense' => $expense,
            'form' => $form,
        ]);
    }

    /** Elimina un gasto validando casa y CSRF. */
    #[Route('/{id}', name: 'app_expense_delete', methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($expense->getHousehold() !== $user->getHousehold()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->getPayload()->getString('_token'))) {
            $expense->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
    }
}
