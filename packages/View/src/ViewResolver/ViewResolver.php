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
        $viewPathWithoutExt = $this->pathResolver->getDirPath('views') . $viewName;

        if (!file_exists($viewPathWithoutExt . '.php')) {
            if (!file_exists($viewPathWithoutExt . '.html')) {
                throw new ViewNotFoundException(
                    "The requested view file '$viewPathWithoutExt.php' or '$viewPathWithoutExt.html' does not exist."
                );
            }
            $viewPath = $viewPathWithoutExt . '.html';
        } else {
            $viewPath = $viewPathWithoutExt . '.php';
        }

        return $viewPath;
    }
}