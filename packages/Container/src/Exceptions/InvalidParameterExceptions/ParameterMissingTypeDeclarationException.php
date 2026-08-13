<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

class ParameterMissingTypeDeclarationException extends InvalidParameterException
{
    protected $message = 'Parameter is missing a type declaration!';
}