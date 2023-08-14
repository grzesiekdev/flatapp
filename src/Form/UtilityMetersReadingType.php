<?php

namespace App\Form;

use App\Entity\UtilityMeterReading;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilityMetersReadingType extends AbstractType
{
    public string $userRole;
    public int $water = 0;
    public int $gas = 0;
    public int $electricity = 0;
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $year = date('Y');
        $this->userRole = $options['userRole'][0];
        if ($options['water']) {
            $this->water = $options['water'];
            $this->gas = $options['gas'];
            $this->electricity = $options['electricity'];
        }

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
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->water
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => 0
            ])
            ->add('water_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => 0
            ])
            ->add('gas_amount', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->gas
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => 0
            ])
            ->add('gas_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => 0
            ])
            ->add('electricity_amount', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->electricity
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => 0
            ])
            ->add('electricity_cost', IntegerType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => 0
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UtilityMeterReading::class,
            'userRole' => null,
            'water' => null,
            'gas' => null,
            'electricity' => null,
        ]);
    }
}