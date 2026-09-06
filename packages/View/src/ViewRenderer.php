<?php
declare(strict_types=1);

namespace Velo\View;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\Session\FlashMessages\Interfaces\FlashMessagesInterface;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\View\ViewResolver\Exceptions\InvalidViewExtensionException;
use Velo\View\ViewResolver\ViewResolver;
use Velo\View\ViewResolver\Exceptions\ViewNotFoundException;

readonly class ViewRenderer
{
    public function __construct(
        private ViewResolver           $viewResolver,
        private SessionInterface       $session,
        private FlashMessagesInterface $flashMessages
    )
    {
    }

    /**
     * @param string $viewFile Should be the file path WITH EXTENSION relative to views path from PathResolver.
     * @param array<string, mixed> $dataToExtract
     *
     * @throws ViewNotFoundException
     * @throws PathNotFoundException
     * @throws InvalidViewExtensionException
     */
    public function render(string $viewFile, array $dataToExtract = []): string
    {
        $viewPath = $this->viewResolver->resolve($viewFile);

        return $this->isPhp($viewPath)
            ? $this->renderPhp($viewPath, $dataToExtract)
            : $this->renderHtml($viewPath);
    }

    /**
     * @throws ViewNotFoundException
     */
    private function renderHtml(string $viewPath): string
    {
        if(($content = file_get_contents($viewPath)) === false) {
            throw new ViewNotFoundException(
                "Failed reading view file '$viewPath'!"
            );
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $dataToExtract
     */
    private function renderPhp(string $viewPathAvoidVariablesCollision, array $dataToExtract = []): string
    {
        $flashMessages = $this->flashMessages;
        $session = $this->session;

        extract($dataToExtract, EXTR_SKIP);
        unset($dataToExtract);

        ob_start();

        require $viewPathAvoidVariablesCollision;

        unset($viewPathAvoidVariablesCollision);

        // Should not return false, because ob_start was called, if it somehow does return false, okay then,
        // TypeError will be thrown when strict_types are enabled, empty string otherwise.
        return ob_get_clean();
    }

    private function isPhp(string $viewPath): bool
    {
        return str_ends_with($viewPath, '.php');
    }
}