<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

class ParameterUnionTypeException extends InvalidParameterException
{
    protected $message = 'Parameter cannot be of a union type!';
}