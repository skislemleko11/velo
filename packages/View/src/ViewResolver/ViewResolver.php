<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;
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
     * @throws ViewNotFoundException
     * @throws PathNotFoundException
     */
    public function resolve(string $viewName): string
    {
        $viewPath = $this->pathResolver->getDirPath('views') . $viewName . '.php';

        if (!file_exists($viewPath)) {
            throw new ViewNotFoundException(
                "The requested view file '$viewPath' does not exist."
            );
        }

        return $viewPath;
    }
}