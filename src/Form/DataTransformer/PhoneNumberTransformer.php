<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PhoneNumberTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private SessionInterface $session;

    public function __construct(ValidatorInterface $validator, SessionInterface $session)
    {
        $this->validator = $validator;
        $this->session = $session;
    }
    private const PHONE_REGEX = '/^(?:(?:\+|00)\d{1,3}[-.\s]?)?(\d{3}[-.\s]?\d{2}[-.\s]?\d{2}[-.\s]?\d{2}|\(?\d{2}\)?[-.\s]?\d{4}[-.\s]?\d{4}|\d{4,}[-.\s]?\d{2,}|\+48\s\d{3}\s\d{3}\s\d{3}|\d{3}\s\d{3}\s\d{3})(?:\s?(?:ext\.?|x)\s?\d{1,5})?$/';

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        $constraint = new Regex([
            'pattern' => self::PHONE_REGEX,
            'message' => 'The phone is not in a valid format.',
        ]);

        $violations = $this->validator->validate($value, $constraint);

        if (count($violations) > 0) {
            $this->session->getFlashBag()->add('error', $violations[0]->getMessage());
            throw new TransformationFailedException($violations[0]->getMessage());
        }

        return $value;
    }
}