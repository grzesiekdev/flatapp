<?php

namespace App\Form;

use App\Entity\Flat;
use phpDocumentor\Reflection\Types\Integer;
use PHPUnit\Util\TextTestListRenderer;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewFlatFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        switch ($options['flow_step']) {
            case 1:
                $builder
                    ->add('area', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ]
                    ])
                    ->add('numberOfRooms', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ]
                    ])
                    ->add('address', TextType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ]
                    ]);
                break;
            case 2:
                $builder
                    ->add('rent', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ]
                    ])
                    ->add('fees', CollectionType::class, [
                        'allow_add' => true,
                        'allow_delete' => true,
                        'entry_type' => FeeType::class,
                        'entry_options' => [
                            'label' => false,
                        ],
                        'prototype' => true,
                        'required' => false
                    ])
                    ->add('deposit', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'required' => false
                    ]);
                break;
            case 3:
                $builder
                    ->add('pictures', FileType::class, [
                        'multiple' => true,
                        'attr'     => [
                            'accept' => 'image/*',
                            'multiple' => 'multiple'
                        ]
                    ])
                    ->add('picturesForTenant', FileType::class, [
                        'multiple' => true,
                        'attr'     => [
                            'accept' => 'image/*',
                            'multiple' => 'multiple'
                        ],
                        'required' => false
                    ]);
                break;
            case 4:
                $builder
                    ->add('description', TextareaType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'required' => false
                    ])
                    ->add('rentAgreement', FileType::class, [
                        'attr' => [
                            'class' => 'form-control',
                            'accept' => 'pdf/*'
                        ],
                        'required' => false
                    ])
                    ->add('furnishing', CollectionType::class, [
                        'allow_add' => true,
                        'allow_delete' => true,
                        'entry_type' => FeeType::class,
                        'entry_options' => [
                            'label' => false
                        ],
                        'prototype' => true,
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'required' => false
                    ]);
                break;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flat::class,
        ]);
    }
}
