<?php
declare(strict_types=1);

namespace Velo\Http;

use Velo\Http\Responses\Response;

interface ResponseRendererInterface
{
    /**
     * @param RequestMethod $requestMethod You must not echo content for the HEAD request method.
     */
    public function render(
        Response      $response,
        RequestMethod $requestMethod = RequestMethod::GET
    ): void;
}