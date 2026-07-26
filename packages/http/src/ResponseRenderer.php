<?php
declare(strict_types=1);

namespace Velo\Http;

/**
 * Renders HttpResponses.
 */
class ResponseRenderer
{
    /**
     * Renders the given HttpResponse.
     */
    public function render(HttpResponse $httpResponse): void
    {
        $this->setStatusCode($httpResponse->statusCode);
        $this->sendHeaders($httpResponse->headers);

        if (isset($httpResponse->headers['Location'])) {
            $this->terminate();
        }

        if ($httpResponse->viewPath) {
            $this->renderView($httpResponse);
        } else {
            $this->echoApiResponse($httpResponse);
        }
    }

    /**
     * Sends HTTP headers from the given array of headers.
     */
    private function sendHeaders(array $headers): void
    {
        if (headers_sent()) {
            return;
        }

        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
    }

    /**
     * Renders the view for the given HttpResponse.
     */
    protected function renderView(HttpResponse $httpResponse): void
    {
        // creating a copy cuz it doesn't work with readonly properties
        $this->extractDataAndRequireView($httpResponse->viewPath, $httpResponse->data + []);
        $this->terminate();
    }

    /**
     * Extracts data and requires the view.
     */
    protected function extractDataAndRequireView(string $viewPathToRequireLongNameToAvoidCollison, array $data): void
    {
        extract($data, EXTR_SKIP);
        require $viewPathToRequireLongNameToAvoidCollison;
    }

    /**
     * Sets headers and echos JSON response.
     */
    protected function echoApiResponse(HttpResponse $httpResponse): void
    {
        $this->setHeader('Content-Type: application/json');
        echo json_encode($httpResponse->data);
        $this->terminate();
    }

    /**
     * Sets header if it's not set.
     */
    protected function setHeader(string $header): void
    {
        if (!headers_sent()) {
            header($header);
        }
    }

    /**
     * Sets status code if headers are not sent.
     */
    protected function setStatusCode(int $statusCode): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }
    }

    /**
     * Terminates the script.
     */
    protected function terminate(): void
    {
        exit;
    }
}