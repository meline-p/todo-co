<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => "Nom d'utilisateur",
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
        if ($options['is_admin']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'required' => true,
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
            ]);
        }
        $builder->add('email', EmailType::class, [
            'label' => 'Adresse email',
            'required' => true,
            'attr' => [
                'class' => 'form-control',
            ],
        ]);

        if (!$options['is_edit']) {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Veuillez entrer un mot de passe.']),
                        new Assert\Length([
                            'min' => 8,
                            'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                            'max' => 4096,
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[a-z]/',
                            'message' => 'Votre mot de passe doit contenir au moins une lettre minuscule.',
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[A-Z]/',
                            'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule.',
                        ]),
                        new Assert\Regex([
                            'pattern' => '/\d/',
                            'message' => 'Votre mot de passe doit contenir au moins un chiffre.',
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[^a-zA-Z\d]/',
                            'message' => 'Votre mot de passe doit contenir au moins un caractère spécial.',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Tapez le mot de passe à nouveau',
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'is_admin' => false
        ]);
    }
}
