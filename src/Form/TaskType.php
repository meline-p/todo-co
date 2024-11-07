<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Titre',
            ])
            ->add('content', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Description',
            ])
            // ->add('author', TextType::class, [
            //     'attr' => [
            //         'class' => 'form-control',
            //     ],
            //     'label' => 'Auteur',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
