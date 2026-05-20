<?php

namespace App\Form;

use App\Entity\Household;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    /** Define campos base de tarea para pantallas Symfony clasicas. */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('completed')
            ->add('createdAt')
            ->add('household', EntityType::class, [
                'class' => Household::class,
                'choice_label' => 'id',
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    /** Vincula este formulario con la entidad Task. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
