<?php
declare(strict_types=1);

namespace Velo\Http;

enum ResponseFormat: string
{
    case HTML = 'text/html';
    case JSON = 'application/json';
    case PLAIN_TEXT = 'text/plain';

    /**
     * Creates a ResponseFormat instance from the given acceptHeader.
     *
     * It's not a real parser, it uses str_contains to search for the preferred format.
     * It prioritizes HTML, then PLAIN_TEXT and JSON is the default case.
     */
    public static function fromGivenAcceptHeader(string $acceptHeader): self
    {
        if (str_contains($acceptHeader, self::HTML->value)) {
            return self::HTML;
        }

        if (str_contains($acceptHeader, self::PLAIN_TEXT->value)) {
            return self::PLAIN_TEXT;
        }

        return self::JSON;
    }

    /**
     * Creates a ResponseFormat instance from $_SERVER superblobal's acceptHeader.
     * It uses fromGivenAcceptHeader method, check its documentation for more details.
     */
    public static function fromGlobalAcceptHeader(): self
    {
        return self::fromGivenAcceptHeader($_SERVER['HTTP_ACCEPT'] ?? '*/*');
    }
}
