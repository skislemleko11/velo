<?php
declare(strict_types=1);

namespace Velo\Controllers;

use Velo\Http\HttpResponse;
use Velo\Router\Exceptions\PageNotFoundException;
use Velo\Router\PathResolver\Exceptions\PathNotFoundException;
use Velo\Router\PathResolver\PathResolver;

abstract class Controller
{
    public function __construct(protected readonly PathResolver $pathResolver)
    {

    }

    /**
     * @throws PathNotFoundException
     * @throws PageNotFoundException
     */
    protected function returnResopnse(?string $viewName = null, array $data = [], int $responseCode = 200): HttpResponse
    {
        if (!$viewName) {
            return new HttpResponse(null, $responseCode, $data);
        }

        $viewPath = $this->pathResolver->getDirPath('views') . $viewName . '.php';

        if (file_exists($viewPath)) {
            return new HttpResponse($viewPath, $responseCode, $data);
        }

        throw new PageNotFoundException();
    }
}