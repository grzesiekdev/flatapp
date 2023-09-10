<?php

namespace App\Form\Specialists;

use App\Entity\Flat;
use App\Entity\Specialist;
use App\Entity\User\Type\Landlord;
use App\Entity\User\User;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class NewSpecialistFormType extends AbstractType
{
    private LandlordRepository $landlordRepository;
    private string $userEmail;
    private Landlord $user;

    public function __construct(LandlordRepository $landlordRepository, Security $security)
    {
        $this->landlordRepository = $landlordRepository;
        $this->userEmail = $security->getUser()->getUserIdentifier();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $options['session'];
        $this->user = $this->landlordRepository->findOneBy(['email' => $this->userEmail]);
        $flats = $this->user->getFlats();

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ]
            ])
            ->add('profession', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ]
            ])
            ->add('email', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'required' => false
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'required' => false
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'required' => false
            ])
            ->add('gmb', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'required' => false,
                'label' => 'Google My Business'
            ])
            ->add('note', TextareaType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'required' => false
            ])
            ->add('flats', ChoiceType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'mapped' => false,
                'choices' => $flats,
                'choice_label' => function (?Flat $flat) {
                    return $flat ? $flat->getAddress() . ', ' . $flat->getArea() . 'm2' : '';
                },
                'multiple' => true,
                'expanded' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Specialist::class,
        ]);
        $resolver->setRequired('session');
    }
}