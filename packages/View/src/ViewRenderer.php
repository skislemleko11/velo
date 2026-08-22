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

    private function renderHtml(string $viewPath): string
    {
        return file_get_contents($viewPath);
    }

    private function renderPhp(string $viewPathAvoidVariablesCollision, array $dataToExtract = []): string
    {
        $flashMessages = $this->flashMessages;
        $session = $this->session;

        extract($dataToExtract, EXTR_SKIP);
        unset($dataToExtract);

        ob_start();

        require $viewPathAvoidVariablesCollision;

        unset($viewPathAvoidVariablesCollision);

        return ob_get_clean();
    }

    private function isPhp(string $viewPath): bool
    {
        return str_ends_with($viewPath, '.php');
    }
}