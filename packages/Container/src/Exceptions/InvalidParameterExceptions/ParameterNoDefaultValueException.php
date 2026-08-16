<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

use Exception;
use Velo\Container\Exceptions\InvalidParameterExceptions\Interfaces\InvalidParameterExceptionInterface;

class ParameterNoDefaultValueException extends Exception implements InvalidParameterExceptionInterface
{
    protected $message = 'Parameter has no default value!';
}