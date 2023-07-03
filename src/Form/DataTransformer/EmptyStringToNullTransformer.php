<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EmptyStringToNullTransformer implements DataTransformerInterface
{

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value == '') {
            throw new TransformationFailedException('Date can\'t be empty');
        }

        return $value;
    }
}