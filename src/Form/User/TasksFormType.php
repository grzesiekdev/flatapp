<?php

namespace App\Form\User;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TasksFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $options['session'];
        $builder
            ->add('description', TextType::class, [
                'attr' => [
                    'class' => 'form-control bg-dark border-0',
                    'placeholder' => 'Enter task'
                ],
                'error_bubbling' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
        $resolver->setRequired('session');
    }
}