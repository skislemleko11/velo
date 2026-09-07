<?php
declare(strict_types=1);

namespace Velo\Middlewares\Exceptions;

use Exception;
use Velo\Middlewares\Exceptions\Interfaces\MiddlewareExceptionInterface;

// TODO: IDK IF IT'S NEEDED AT ALL, I'M THINKING ABOUT DELETING IT, CUZ ACTUALLY WHAT'S THE POINT OF IT IN ANTI CSRF MIDDLEWARE?
// TODO: GET IS NOT THE ONLY METHOD WHICH SHOULD NOT BE USED WITH ANTI CSRF AND IDK, WHY AM I CHECKING IT AT ALL?
class InvalidRequestMethodMiddlewareExceptionInterface extends Exception implements MiddlewareExceptionInterface
{

}