<?php
declare(strict_types=1);

namespace Velo\Middlewares\Exceptions;

use Exception;
use Velo\Middlewares\Exceptions\Interfaces\MiddlewareExceptionInterface;

class InvalidRequestMethodMiddlewareExceptionInterface extends Exception implements MiddlewareExceptionInterface
{

}