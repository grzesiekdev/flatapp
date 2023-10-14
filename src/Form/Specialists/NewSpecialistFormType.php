<?php

namespace App\Form\Specialists;

use App\Entity\Flat;
use App\Entity\Specialist;
use App\Entity\User\Type\Landlord;
use App\Entity\User\User;
use App\Form\DataTransformer\EmailTransformer;
use App\Form\DataTransformer\PhoneNumberTransformer;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NewSpecialistFormType extends AbstractType
{
    private LandlordRepository $landlordRepository;
    private string $userEmail;
    private Landlord $user;
    private ValidatorInterface $validator;
    private SessionInterface $session;

    public function __construct(LandlordRepository $landlordRepository, Security $security, ValidatorInterface $validator)
    {
        $this->landlordRepository = $landlordRepository;
        $this->userEmail = $security->getUser()->getUserIdentifier();
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->session = $options['session'];
        $specialistFlats = $options['specialist_flats'];

        $this->user = $this->landlordRepository->findOneBy(['email' => $this->userEmail]);
        $flats = $this->user->getFlats();

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Name too long',
                    ]),
                ],
                'error_bubbling' => true,
            ])
            ->add('profession', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Profession too long',
                    ]),
                ],
                'error_bubbling' => true,
            ])
            ->add('email', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 70,
                        'maxMessage' => 'Email too long',
                    ]),
                ],
                'required' => false,
                'error_bubbling' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 15,
                        'maxMessage' => 'Phone too long',
                    ]),
                ],
                'required' => false,
                'error_bubbling' => true,
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Address too long',
                    ]),
                ],
                'required' => false,
                'error_bubbling' => true,
            ])
            ->add('gmb', TextType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'GMB address too long',
                    ]),
                ],
                'required' => false,
                'label' => 'Google My Business',
                'error_bubbling' => true,
            ])
            ->add('note', TextareaType::class, [
                'attr' => ['class' => '
                        form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Note too long',
                    ]),
                ],
                'required' => false,
                'error_bubbling' => true,
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
                'expanded' => true,
                'data' => $specialistFlats
            ]);
        $builder->get('phone')
            ->addModelTransformer(new PhoneNumberTransformer($this->validator, $this->session));
        $builder->get('email')
            ->addModelTransformer(new EmailTransformer($this->validator, $this->session));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Specialist::class,
            'specialist_flats' => null
        ]);
        $resolver->setRequired('session');
    }
}