<?php
declare(strict_types=1);

namespace Velo\Http;

class RedirectUrl
{
    public static function withRedirectParam(string $baseUrl, string $redirectUrl): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . http_build_query(
                ['redirect' => $redirectUrl]
            );
    }
}