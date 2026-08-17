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
    public function render(HttpResponse $httpResponse, RequestMethod $requestMethod = RequestMethod::GET): void
    {
        ob_start();

        if (!isset($httpResponse->headers['Location'])) {
            if ($httpResponse->viewPath) {
                $this->renderView($httpResponse);
            } else {
                echo $this->getApiResponse($httpResponse);
            }
        }

        $content = ob_get_clean();

        if (!isset($httpResponse->headers['Content-Length'])) {
            $httpResponse->setHeader('Content-Length', (string) strlen($content));
        }

        $this->emitter->setStatusCode($httpResponse->statusCode)
            ->sendHeaders($httpResponse->headers);

        if ($requestMethod !== RequestMethod::HEAD) {
            echo $content;
        }

        $this->emitter->terminate();
    }

    /**
     * Renders the view for the given HttpResponse.
     */
    private function renderView(HttpResponse $httpResponse): void
    {
        // creating a copy cuz it doesn't work with readonly properties
        $this->extractDataAndRequireView($httpResponse->viewPath, $httpResponse->body + []);
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
     * @throws JsonException
     */
    private function getApiResponse(HttpResponse $httpResponse): string
    {
        return is_array($httpResponse->body) ? $this->getJsonApiResponse($httpResponse->body) : $httpResponse->body;
    }

    /**
     * @throws JsonException
     */
    private function getJsonApiResponse(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}