<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

use Exception;
use Velo\Container\Exceptions\InvalidParameterExceptions\Interfaces\InvalidParameterExceptionInterface;

class UnexpectedInvalidParameterException extends Exception implements InvalidParameterExceptionInterface
{

}