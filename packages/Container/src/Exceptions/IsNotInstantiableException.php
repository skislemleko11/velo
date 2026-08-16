<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions;

use Exception;
use Velo\Container\Exceptions\Interfaces\ContainerExceptionInterface;

class IsNotInstantiableException extends Exception implements ContainerExceptionInterface
{
    protected $message = 'The given Class/Interface is not instantiable!';
}