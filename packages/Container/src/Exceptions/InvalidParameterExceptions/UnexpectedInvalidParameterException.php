<?php
declare(strict_types=1);

namespace Velo\Container\Exceptions\InvalidParameterExceptions;

use Exception;
use Velo\Container\Exceptions\InvalidParameterExceptions\Interfaces\InvalidParameterExceptionInterface;

final class UnexpectedInvalidParameterException extends Exception implements InvalidParameterExceptionInterface
{
    protected $message = "An unexpected invalid parameter exception occurred. It's CRITICAL, it should not be possible!";
}