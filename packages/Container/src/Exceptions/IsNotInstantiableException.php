<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Velo\Exceptions\Interfaces\VeloExceptionInterface;

final class IsNotInstantiableException extends Exception implements VeloExceptionInterface, ContainerExceptionInterface
{
    protected $message = 'The given Class/Interface is not instantiable!';
}