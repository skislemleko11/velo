<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver\Exceptions;

use Velo\Exceptions\NotFoundException;
use Velo\View\Exceptions\Interfaces\ViewExceptionInterface;

class ViewNotFoundException extends NotFoundException implements ViewExceptionInterface
{
    protected $message = 'The requested view file was not found!';

    public function shouldLogException(): bool
    {
        return true;
    }
}