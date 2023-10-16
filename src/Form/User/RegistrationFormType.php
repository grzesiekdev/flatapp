<?php

namespace App\Form\User;

use App\Entity\User\User;
use App\Form\DataTransformer\Base32CodeTransformer;
use App\Form\DataTransformer\EmailTransformer;
use App\Form\DataTransformer\EmptyStringToNullTransformer;
use App\Form\DataTransformer\PhoneNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationFormType extends AbstractType
{
    private ValidatorInterface $validator;
    private SessionInterface $session;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $this->session = $options['session'];

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
                'error_bubbling' => true,
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
                'error_bubbling' => true,
            ])
            ->add('dateOfBirth', DateType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'years' =>  range((int)date('Y') - 80, (int)date('Y')),
                'months' => range(1, 12),
                'days' => range(1, 31),
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
            ->add('image', FileType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image',
                    ])
                ],
                'required' => false,
                'label' => 'Profile image',
                'error_bubbling' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => '
                form-control',
                ],
                'constraints' => [
                    new Length([
                        'max' => 15,
                        'maxMessage' => 'Phone number too long',
                    ]),
                ],
                'required' => false,
                'error_bubbling' => true,
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
                'label_html' => true,
                'error_bubbling' => true,
            ])
            ->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
                'mapped' => false,
                'error_bubbling' => true,
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

        $builder->get('code')
            ->addModelTransformer(new Base32CodeTransformer($this->validator, $this->session));
        $builder->get('phone')
            ->addModelTransformer(new PhoneNumberTransformer($this->validator, $this->session));
        $builder->get('email')
            ->addModelTransformer(new EmailTransformer($this->validator, $this->session));
        $builder->get('dateOfBirth')
            ->addModelTransformer(new EmptyStringToNullTransformer($this->session));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
        $resolver->setRequired('session');
    }
}
