<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver\Exceptions;

use Exception;
use Velo\View\Exceptions\Interfaces\ViewExceptionInterface;

class InvalidViewExtensionException extends Exception implements ViewExceptionInterface
{
    protected $message = "The requested view file does not end either with '.html' or '.php'.";
}