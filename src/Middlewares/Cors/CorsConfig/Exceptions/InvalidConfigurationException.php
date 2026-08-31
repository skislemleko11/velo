<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors\CorsConfig\Exceptions;

use Exception;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;
use Velo\Middlewares\Exceptions\Interfaces\MiddlewareExceptionInterface;

class InvalidConfigurationException extends Exception implements MiddlewareExceptionInterface
{
    protected $message = 'Invalid configuration of ' . CorsConfig::class;
}