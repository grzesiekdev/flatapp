<?php

namespace App\Form;

use App\Entity\UtilityMeterReading;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilityMetersReadingType extends AbstractType
{
    public string $userRole;
    public float $water = 0;
    public float $gas = 0;
    public float $electricity = 0;
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $year = date('Y');
        $this->userRole = $options['userRole'][0];
        if ($options['water'] || $options['gas'] || $options['electricity']) {
            $this->water = $options['water'];
            $this->gas = $options['gas'];
            $this->electricity = $options['electricity'];
        }

        $builder
            ->add('date', DateType::class, [
                'attr' => ['class' => '
                    form-control',
                    'disabled' => true,
                ],
                'widget' => 'single_text',
                'years' => range($year, $year),
                'error_bubbling' => true,
                'input_format' => 'dd-mm-yyyy',
                'data' => new DateTime('now'),
                'required' => false
            ])
            ->add('water_amount', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->water
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('water_cost', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                    'placeholder' => 0
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('gas_amount', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->gas
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('gas_cost', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                    'placeholder' => 0
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('electricity_amount', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control mt-1',
                    'placeholder' => $this->electricity
                ],
                'disabled' => $this->userRole != 'ROLE_TENANT',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('electricity_cost', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1',
                    'placeholder' => 0
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => '0',
                'scale' => 2
            ])
            ->add('invoices', FileType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => '
                    form-control mt-1'
                ],
                'disabled' => $this->userRole != 'ROLE_LANDLORD',
                'empty_data' => null,
                'multiple' => true,
                'label' => false
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