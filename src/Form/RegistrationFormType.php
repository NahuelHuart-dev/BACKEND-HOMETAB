<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Household;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    /** Define los campos del registro web clasico. */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'nom@exemple.com'
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Joan'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Cognoms',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'García López'
                ]
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Telèfon (opcional)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+34 666 777 888'
                ]
            ])
            ->add('household', EntityType::class, [
                'class' => Household::class,
                'choice_label' => 'name',
                'label' => 'Llar',
                'placeholder' => 'Selecciona una llar',
                'attr' => ['class' => 'form-select'],
                'help' => 'Si la teva llar no existeix, contacta amb l\'administrador'
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Contrasenya',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'placeholder' => 'Mínim 6 caràcters'
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Introdueix una contrasenya',
                        ]),
                        new Length(
                            min: 6,
                            max: 4096,
                            minMessage: 'La contrasenya ha de tenir almenys {{ limit }} caràcters'
                        ),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirma la contrasenya',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'placeholder' => 'Repeteix la contrasenya'
                    ],
                ],
                'invalid_message' => 'Les contrasenyes no coincideixen',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Accepto els termes i condicions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Has d\'acceptar els termes i condicions.',
                    ]),
                ],
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    /** Vincula este formulario con la entidad User. */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
