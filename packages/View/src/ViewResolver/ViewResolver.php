<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\View\ViewResolver\Exceptions\InvalidViewExtensionException;
use Velo\View\ViewResolver\Exceptions\ViewNotFoundException;

/**
 * Resolves Views using PathResolver.
 */
readonly class ViewResolver
{
    public function __construct(private PathResolver $pathResolver)
    {
    }

    /**
     * Resolves the path to a view file.
     *
     * @param string $viewFile Should be the file path WITH EXTENSION relative to views path from PathResolver. It must be either an HTML or a PHP file.
     *
     * @throws ViewNotFoundException
     * @throws PathNotFoundException
     * @throws InvalidViewExtensionException
     */
    public function resolve(string $viewFile): string
    {
        if (!str_ends_with($viewFile, '.php') && !str_ends_with($viewFile, '.html')) {
            throw new InvalidViewExtensionException(
                "The requested view file '$viewFile' does not end either with '.html' or '.php'."
            );
        }

        $viewPath = $this->pathResolver->getDirPath('views') . $viewFile;

        if (!is_file($viewPath) || !is_readable($viewPath)) {
            throw new ViewNotFoundException(
                "The requested view file '$viewPath' does not exist or is not readable!"
            );
        }

        return $viewPath;
    }
}