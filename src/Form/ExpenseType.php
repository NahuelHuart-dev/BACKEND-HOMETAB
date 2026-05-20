<?php

namespace App\Form;

use App\Entity\Expense;
use App\Entity\Household;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseType extends AbstractType
{
    /** Define campos base de gasto para pantallas Symfony clasicas. */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('amount')
            ->add('category')
            ->add('paidAt')
            ->add('isPaid')
            ->add('notes')
            ->add('paidBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Pagado por',
            ])
            ->add('splitUsers', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'multiple' => true,
                'mapped' => false,
                'label' => 'Dividir entre',
            ])
        ;
    }

    /** Vincula este formulario con la entidad Expense. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
        ]);
    }
}
