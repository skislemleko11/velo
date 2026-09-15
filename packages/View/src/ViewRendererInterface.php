<?php
declare(strict_types=1);

namespace Velo\View;

interface ViewRendererInterface
{
    /**
     * @return string Content to echo.
     */
    public function render(string $viewFile, array $dataToExtract = []): string;
}