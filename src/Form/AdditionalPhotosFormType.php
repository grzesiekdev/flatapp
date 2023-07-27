<?php

namespace App\Form;

use App\Entity\Flat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdditionalPhotosFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $options['session'];

        $builder
            ->add('picturesForTenant', FileType::class, [
                'multiple' => true,
                'attr'     => [
                    'accept' => 'image/*',
                    'multiple' => 'multiple'
                ],
                'label' => 'Additional photos',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flat::class,
        ]);
        $resolver->setRequired('session');
    }
}