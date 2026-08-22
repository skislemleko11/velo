<?php
declare(strict_types=1);

namespace Velo\Http;

use Velo\View\ViewRenderer;

readonly class RenderContext
{
    public function __construct(
        public ViewRenderer $viewRenderer
    )
    {
    }
}