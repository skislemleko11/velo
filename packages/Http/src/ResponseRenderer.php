<?php
declare(strict_types=1);

namespace Velo\Http;

use Velo\Http\Emitter\Interfaces\EmitterInterface;
use Velo\Http\Responses\Response;

/**
 * Renders Responses.
 */
readonly class ResponseRenderer
{
    public function __construct(
        private EmitterInterface $emitter,
        private RenderContext    $renderContext,
    )
    {
    }

    /**
     * Renders the given HttpResponse.
     */
    public function render(
        Response      $response,
        RequestMethod $requestMethod = RequestMethod::GET
    ): void
    {
        $content = $response->render($this->renderContext);

        $contentLength = $this->setContentLengthHeaderIfNotSetAndReturnIt($response, $content);

        $this->emitter->setStatusCode($contentLength === 0 ? 204 : $response->statusCode)
            ->sendHeaders($response->getHeaders());

        $this->echoContentIfRequestMethodIsNotHead($content, $requestMethod);

        $this->emitter->terminate();
    }

    private function setContentLengthHeaderIfNotSetAndReturnIt(Response $response, string $content): int
    {
        if ($response->getHeader('Content-Length') === null) {
            $length = strlen($content);

            $response->setHeader('Content-Length', (string)$length);
        }

        return $length ?? (int)$response->getHeader('Content-Length');
    }

    private function echoContentIfRequestMethodIsNotHead(string $content, RequestMethod $requestMethod): void
    {
        if ($requestMethod !== RequestMethod::HEAD) {
            echo $content;
        }
    }
}