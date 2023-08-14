<?php

namespace App\Form;

use App\Entity\UtilityMeterReading;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilityMetersReadingType extends AbstractType
{
    public string $userRole;
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $year = date('Y');
        $this->userRole = $options['userRole'][0];

        $builder
            ->add('date', DateType::class, [
                'attr' => ['class' => '
                    form-control',
                    'placeholder' => 'dd-mm-yyyy',
                    'disabled' => true,
                ],
                'widget' => 'single_text',
                'years' => range($year, $year),
                'error_bubbling' => true,
                'input_format' => 'dd-mm-yyyy',
                'data' => new DateTime('now'),
                'required' => false
            ])
            ->add('water_amount', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT'
            ])
            ->add('water_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD'
            ])
            ->add('gas_amount', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT'
            ])
            ->add('gas_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD'
            ])
            ->add('electricity_amount', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT'
            ])
            ->add('electricity_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilityMeterReading::class,
            'userRole' => null
        ]);
    }
}