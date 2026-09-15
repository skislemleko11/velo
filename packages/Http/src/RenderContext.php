<?php
declare(strict_types=1);

namespace Velo\Http;

use Velo\View\ViewRendererInterface;

readonly class RenderContext
{
    public function __construct(
        public ViewRendererInterface $viewRenderer
    )
    {
    }
}