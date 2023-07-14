<?php

namespace App\Form;

use App\Entity\User\User;
use App\Form\DataTransformer\Base32CodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvitationCodeFormType extends AbstractType
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $options['session'];

        $builder
            ->add('code', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'mapped' => false,
                'error_bubbling' => true,
            ]);
        $builder->get('code')
            ->addModelTransformer(new Base32CodeTransformer($this->validator, $session));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('session');
    }
}