<?php
declare(strict_types=1);

namespace Velo\FileSystem\PathResolver\Exceptions;

use Exception;
use Velo\FileSystem\PathResolver\Exceptions\Interfaces\PathResolverExceptionInterface;

/**
 * This Exception should be thrown when a Path in PathResolver was not found.
 */
class PathNotFoundException extends Exception implements PathResolverExceptionInterface
{
    protected $message = 'The requested path not found!';
}