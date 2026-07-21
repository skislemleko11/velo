<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

class ParameterUnionTypeHintException extends InvalidParameterException
{
    protected $message = 'Parameter has a union type hint!';
}