<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver\Exceptions;

use Velo\View\Exceptions\ViewException;

class ViewNotFoundException extends ViewException
{
    protected $message = 'The requested view file was not found!';
}