<?php

namespace App\Form;

use App\Entity\User\User;
use App\Form\DataTransformer\EmptyStringToNullTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $options['session'];
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'error_bubbling' => true,
            ])
            ->add('dateOfBirth', DateType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'years' => range(date('Y')-80, date('Y')),
                'error_bubbling' => true,
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false,
                'error_bubbling' => true,
            ])
            ->add('image', FileType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false,
                'label' => 'Profile image',
                'mapped' => false,
                'error_bubbling' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false,
                'error_bubbling' => true,
            ]);

        $builder->get('dateOfBirth')
            ->addModelTransformer(new EmptyStringToNullTransformer($session));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
        $resolver->setRequired('session');
    }
}