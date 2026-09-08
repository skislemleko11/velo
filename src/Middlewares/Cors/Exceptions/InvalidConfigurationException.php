<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors\Exceptions;

use Exception;
use Velo\Middlewares\Cors\CorsConfig;
use Velo\Middlewares\Exceptions\Interfaces\MiddlewareExceptionInterface;

final class InvalidConfigurationException extends Exception implements MiddlewareExceptionInterface
{
    protected $message = 'Invalid configuration of ' . CorsConfig::class;
}