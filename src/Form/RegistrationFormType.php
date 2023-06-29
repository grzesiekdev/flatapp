<?php

namespace App\Form;

use App\Entity\User\User;
use App\Entity\User\UserRegistration;
use DateTime;
use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ]
            ])
            ->add('email', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'options' => ['attr' => ['class' => 'form-control']],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ])
            ->add('dateOfBirth', DateType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'years' => range(date('Y')-80, date('Y')),
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false
            ])
            ->add('image', FileType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false,
                'label' => 'Profile image'
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'required' => false
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Landlord <br> <small>I want to add new flats for rent and invite tenants</small>' => 'ROLE_LANDLORD',
                    'Tenant <br> <small>I want to rent flat and I have code from landlord</small>' => 'ROLE_TENANT',
                ],
                'attr' => ['class' => 'form-control'],
                'expanded' => true,
                'multiple' => false,
                'label' => 'I want to register as:',
                'placeholder' => false,
                'label_html' => true
            ])
            ->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
                'mapped' => false,
            ])
        ;
        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return count($rolesArray)? $rolesArray[0]: null;
                },
                function ($rolesString) {
                    // transform the string back to an array
                    return [$rolesString];
                }
            ));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
