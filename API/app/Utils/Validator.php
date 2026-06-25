<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    private static ?ValidatorInterface $instance = null;

    public static function getInstance(): ValidatorInterface
    {
        if (self::$instance === null) {
            self::$instance = Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator();
        }

        return self::$instance;
    }
}
