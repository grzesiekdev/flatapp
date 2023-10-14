<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private SessionInterface $session;

    public function __construct(ValidatorInterface $validator, SessionInterface $session)
    {
        $this->validator = $validator;
        $this->session = $session;
    }
    private const EMAIL_REGEX = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        $constraint = new Regex([
            'pattern' => self::EMAIL_REGEX,
            'message' => 'The email is not in a valid format.',
        ]);

        $violations = $this->validator->validate($value, $constraint);

        if (count($violations) > 0) {
            $this->session->getFlashBag()->add('error', $violations[0]->getMessage());
            throw new TransformationFailedException($violations[0]->getMessage());
        }

        return $value;
    }
}