<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Household;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class UserType extends AbstractType
{
    /** Define los campos de usuario y password opcional segun modo crear/editar. */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'El nom és obligatori',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Cognom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'El cognom és obligatori',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'L’email és obligatori',
                    ]),
                ],

            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Telèfon',
                'required' => false,
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Biografia',
                'required' => false,
            ]);

        if ($options['allow_household']) {
            $builder->add('household', EntityType::class, [
                'class' => Household::class,
                'choice_label' => 'name',
                'label' => 'Casa (opcional)',
                'placeholder' => 'Sin casa',
                'mapped' => false,
                'required' => false,
            ]);
        }

        if ($options['allow_global_role']) {
            $builder->add('role', ChoiceType::class, [
                'label' => 'Rol global',
                'mapped' => false,
                'required' => true,
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                    'Super admin' => 'ROLE_SUPER_ADMIN',
                ],
                'data' => 'ROLE_USER',
            ]);
        }

        if ($options['allow_active']) {
            $builder->add('isActive', CheckboxType::class, [
                'label' => 'Usuario activo',
                'required' => false,
            ]);
        }

        /**
         * Contrasenya:
         * - obligatori en CREAR
         * - opcional en EDITAR
         */
        $builder->add('plainPassword', PasswordType::class, [
            'label' => $options['is_edit']
                ? 'Nova contrasenya (opcional)'
                : 'Contrasenya',
            'mapped' => false,
            'required' => !$options['is_edit'],
            'constraints' => $options['is_edit']
                ? [
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'La contrasenya ha de tenir almenys {{ limit }} caràcters'
                    ),
                ]
                : [
                    new NotBlank([
                        'message' => 'La contrasenya és obligatòria',
                    ]),
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'La contrasenya ha de tenir almenys {{ limit }} caràcters'
                    ),
                ],
        ]);


        if ($options['allow_roles']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rols',
                'choices' => [
                    'Usuari' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
            ]);
        }
    }



    /** Configura opciones extra para edicion y roles visibles. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false, // Per defecte, el formulari es cridarà per crear un nou usuari
            'allow_roles' => false, // Per defecte, només l’admin pot veure els rols
            'allow_household' => false,
            'allow_global_role' => false,
            'allow_active' => false,

        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('allow_roles', 'bool');
        $resolver->setAllowedTypes('allow_household', 'bool');
        $resolver->setAllowedTypes('allow_global_role', 'bool');
        $resolver->setAllowedTypes('allow_active', 'bool');
    }
}
