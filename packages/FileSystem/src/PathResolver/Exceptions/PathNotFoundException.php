<?php
declare(strict_types=1);

namespace Velo\FileSystem\PathResolver\Exceptions;

/**
 * This Exception should be thrown when a Path in PathResolver was not found.
 */
class PathNotFoundException extends PathResolverException
{
    protected $message = 'The requested path not found!';
}