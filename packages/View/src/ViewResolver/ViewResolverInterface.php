<?php
declare(strict_types=1);

namespace Velo\View\ViewResolver;

use Velo\View\ViewResolver\Exceptions\InvalidViewExtensionException;
use Velo\View\ViewResolver\Exceptions\ViewNotFoundException;

interface ViewResolverInterface
{
    /**
     * @throws ViewNotFoundException
     * @throws InvalidViewExtensionException
     */
    public function resolve(string $viewFile): string;
}