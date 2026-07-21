<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions;

class IsNotInstantiableException extends ContainerException
{
    protected $message = 'The given Class/Interface is not instantiable!';
}