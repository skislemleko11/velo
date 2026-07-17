<?php
declare(strict_types=1);

namespace Velo\Http;

class ResponseRenderer
{
    public function render(HttpResponse $httpResponse): void
    {
        $this->setStatusCode($httpResponse->statusCode);

        if ($httpResponse->viewPath)
            $this->renderView($httpResponse);
        else
            $this->echoApiResponse($httpResponse);
    }

    protected function renderView(HttpResponse $httpResponse): void
    {
        // creating a copy cuz it doesn't work with readonly properties
        $this->requireView($httpResponse->viewPath, $httpResponse->data + []);
        $this->terminate();
    }

    protected function requireView(string $viewPathToRequireLongNameToAvoidCollison, array $data): void
    {
        extract($data, EXTR_SKIP);
        require $viewPathToRequireLongNameToAvoidCollison;
    }

    protected function echoApiResponse(HttpResponse $httpResponse): void
    {
        $this->setHeader('Content-Type: application/json');
        echo json_encode($httpResponse->data);
        $this->terminate();
    }

    protected function setHeader(string $header): void
    {
        if (!headers_sent())
            header($header);
    }

    protected function setStatusCode(int $statusCode): void
    {
        if (!headers_sent())
            http_response_code($statusCode);
    }

    protected function terminate(): void
    {
        exit;
    }
}