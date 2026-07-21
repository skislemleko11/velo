<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

class ParameterIntersectionTypeHintException extends InvalidParameterException
{
    protected $message = 'Parameter has an intersection type hint!';
}