<?php
declare(strict_types=1);

namespace Velo\Middlewares\Exceptions;

use InvalidArgumentException;

class CannotUseAntiCsrfMiddlewareWithGetMethodException extends InvalidArgumentException
{

}