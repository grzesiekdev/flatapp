<?php

namespace App\Form\Flat;

use App\Entity\Flat;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

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
                        ],
                    ])
                    ->add('numberOfRooms', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                    ])
                    ->add('address', TextType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ]
                    ])
                    ->add('floor', ChoiceType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'choices' => [
                            'Ground floor' => 0,
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                        ]
                    ])
                    ->add('maxFloor', ChoiceType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'choices' => [
                            '1' => 1,
                            '2' => 2,
                            '3' => 3,
                            '4' => 4,
                            '5' => 5,
                            '6' => 6,
                            '7' => 7,
                            '8' => 8,
                            '9' => 9,
                            '10' => 10,
                            '11' => 11,
                            '12' => 12,
                            '13' => 13,
                            '14' => 14,
                            '15' => 15,
                            '16' => 16,
                        ],
                        'label' => 'Floors in building'
                    ]);
                break;
            case 2:
                $builder
                    ->add('rent', IntegerType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'constraints' => [
                            new Assert\NotBlank(
                                message: 'Rent cannot be 0'
                            )
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
                        ],
                        'required' => false
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
                    ->add('description', CKEditorType::class, [
                        'attr' => [
                            'class' => 'form-control',
                            'placeholder' => 'Flat description...',
                        ],
                        'config' => [
                            'resize_enabled' => false,
                            'height' => '300px',
                            'toolbar' => 'flat_toolbar'
                        ],
                        'constraints' => [
                            new Length([
                                'max' => 4000,
                                'maxMessage' => 'Description too long',
                            ]),
                        ],
                        'required' => false,
                        'sanitize_html' => true
                    ])
                    ->add('rentAgreement', FileType::class, [
                        'attr' => [
                            'class' => 'form-control',
                            'accept' => 'pdf/*'
                        ],
                        'constraints' => [
                            new File([
                                'maxSize' => '20M',
                                'mimeTypes' => ['application/pdf', 'application/x-pdf'],
                                'mimeTypesMessage' => 'Please upload a valid PDF document',
                            ]),
                        ],
                        'required' => false,
                        'data_class' => null
                    ])
                    ->add('furnishing', ChoiceType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'required' => false,
                        'label' => 'Furnishing',
                        'choices' => [
                            '<i class="fa-regular text-danger fa-circle-xmark"></i> No furniture' => false,
                            '<i class="fas fa-couch fa-lg"></i> Furnished <small>(wardrobes, kitchen furniture, etc)</small>' => 'furnished',
                            '<i class="fa-solid fa-fire-burner fa-lg"></i> Stove' => 'stove',
                            '<i class="fa-solid fa-utensils fa-lg"></i> Utensils' => 'utensils',
                            '<i class="fa-solid fa-kitchen-set fa-lg"></i> Kitchen set' => 'kitchen set',
                            '<i class="fa-solid fa-bath fa-lg"></i> Bath' => 'bath',
                            '<i class="fa-solid fa-shower fa-lg"></i> Shower' => 'shower',
                            '<i class="fa-solid fa-bed fa-lg"></i> Bed' => 'bed',
                            '<i class="fa-solid fa-tv fa-lg"></i> TV' => 'tv',
                        ],
                        'expanded' => true,
                        'multiple' => true,
                        'label_html' => true
                    ])
                    ->add('additionalFurnishing', TextType::class, [
                        'attr' => ['class' => '
                        form-control',
                        ],
                        'label_html' => true,
                        'constraints' => [
                            new Length([
                                'max' => 255,
                                'maxMessage' => 'Additional furnishing too long',
                            ]),
                        ],
                        'label' => 'Additional furniture <small>(add here anything that isn\'t listed above)</small>',
                        'required' => false
                    ]);
                $builder->get('furnishing')
                    ->addModelTransformer(new CallbackTransformer(
                        function ($furnishingArray) {
                            return count($furnishingArray)? $furnishingArray[0]: null;
                        },
                        function ($furnishingString) {
                            return [$furnishingString];
                        }
                    ));
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
