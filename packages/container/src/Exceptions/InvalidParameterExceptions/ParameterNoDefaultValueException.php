<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

class ParameterNoDefaultValueException extends InvalidParameterException
{
    protected $message = 'Parameter has no default value!';
}