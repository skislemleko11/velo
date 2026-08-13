<?php
declare(strict_types=1);

namespace Velo\Http;

use JsonException;
use Velo\Http\Emitter\Interfaces\EmitterInterface;
use Velo\Session\FlashMessages\Interfaces\FlashMessagesInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

/**
 * Renders HttpResponses.
 */
readonly class ResponseRenderer
{
    public function __construct(
        private EmitterInterface       $emitter,
        private SessionInterface       $session,
        private FlashMessagesInterface $flashMessages
    )
    {
    }

    /**
     * Renders the given HttpResponse.
     *
     * @throws JsonException
     */
    public function render(HttpResponse $httpResponse): void
    {
        $this->emitter->setStatusCode($httpResponse->statusCode)
            ->sendHeaders($httpResponse->headers);

        if (isset($httpResponse->headers['Location'])) {
            $this->emitter->terminate();
        }

        if ($httpResponse->viewPath) {
            $this->renderView($httpResponse);
        } else {
            $this->echoApiResponse($httpResponse);
        }
    }

    /**
     * Renders the view for the given HttpResponse.
     */
    private function renderView(HttpResponse $httpResponse): void
    {
        // creating a copy cuz it doesn't work with readonly properties
        $this->extractDataAndRequireView($httpResponse->viewPath, $httpResponse->data + []);

        $this->emitter->terminate();
    }

    /**
     * Extracts data and requires the view.
     */
    private function extractDataAndRequireView(string $viewPathToRequireLongNameToAvoidCollison, array $data): void
    {
        $session = $this->session;
        $flashMessages = $this->flashMessages;

        extract($data, EXTR_SKIP);

        require $viewPathToRequireLongNameToAvoidCollison;
    }

    /**
     * Sets headers and echos JSON response.
     *
     * @throws JsonException
     */
    private function echoApiResponse(HttpResponse $httpResponse): void
    {
        if (is_array($httpResponse->data)) {
            $this->echoJsonApiResponse($httpResponse->data);
        } else {
            $this->echoPlainTextResponse($httpResponse->data);
        }

        $this->emitter->terminate();
    }

    /**
     * @throws JsonException
     */
    private function echoJsonApiResponse(array $data): void
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function echoPlainTextResponse(string $content): void
    {
        echo $content;
    }
}