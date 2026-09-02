<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

use Velo\Http\Responses\Concrete\NoContentResponse;
use Velo\Http\Responses\Response;
use Velo\Middlewares\Cors\CorsConfig\CorsConfig;

final readonly class CorsResponseProcessor
{
    private const string VARY_HEADER = 'vary';

    public function __construct(
        private CorsConfig $config,
        private string $origin
    )
    {
    }

    public function buildPreflightResponse(): NoContentResponse
    {
        $response = new NoContentResponse();

        $this->setAllowOriginHeader($response)
            ->setAllowCredentialsHeaderIfAllowsCredentials($response)
            ->setVaryHeaderForPreflight($response)
            ->setAllowedMethodsHeader($response)
            ->setAllowedHeadersHeaderIfNotEmpty($response)
            ->setMaxAgeHeader($response);

        return $response;
    }

    public function addCorsHeaders(Response $response): Response
    {
        $this->setAllowOriginHeader($response)
            ->setVaryHeaderDependingOnCredentialsAndOrigin($response)
            ->setAllowCredentialsHeaderIfAllowsCredentials($response)
            ->setExposeHeadersHeaderIfNotEmpty($response);

        return $response;
    }

    private function setAllowOriginHeader(Response $response): self
    {
        $origin = $this->config->allowAllOrigins ? '*' : $this->origin;

        $response->setHeader(CorsResponseHeaderName::ALLOW_ORIGIN->value, $origin);

        return $this;
    }

    private function setAllowCredentialsHeaderIfAllowsCredentials(Response $response): self
    {
        if ($this->config->allowCredentials) {
            $response->setHeader(CorsResponseHeaderName::ALLOW_CREDENTIALS->value, 'true');
        }

        return $this;
    }

    private function setVaryHeaderDependingOnCredentialsAndOrigin(Response $response): self
    {
        if ($this->config->allowCredentials || !$this->config->allowAllOrigins) {
            $response->appendValueToHeader(self::VARY_HEADER, CorsRequestHeaderName::ORIGIN->value);
        }

        return $this;
    }

    private function setVaryHeaderForPreflight(Response $response): self
    {
        $this->setVaryHeaderDependingOnCredentialsAndOrigin($response);

        $response->appendValueToHeader(self::VARY_HEADER, CorsRequestHeaderName::REQUEST_METHOD->value);

        if ($this->config->allowedHeaders) {
            $response->appendValueToHeader(self::VARY_HEADER, CorsRequestHeaderName::REQUEST_HEADERS->value);
        }

        return $this;
    }

    private function setAllowedMethodsHeader(Response $response): self
    {
        $methodsString = implode(
            ', ',
            array_map(fn($method) => $method->value, $this->config->allowedMethods)
        );

        $response->setHeader(CorsResponseHeaderName::ALLOW_METHODS->value, $methodsString);

        return $this;
    }

    private function setAllowedHeadersHeaderIfNotEmpty(Response $response): self
    {
        $allowedHeaders = $this->config->allowedHeaders;

        if ($allowedHeaders) {
            $response->setHeader(
                CorsResponseHeaderName::ALLOW_HEADERS->value,
                implode(', ', $allowedHeaders)
            );
        }

        return $this;
    }

    private function setMaxAgeHeader(Response $response): self
    {
        $response->setHeader(CorsResponseHeaderName::MAX_AGE->value, (string)$this->config->maxAgeSeconds);

        return $this;
    }

    private function setExposeHeadersHeaderIfNotEmpty(Response $response): self
    {
        $exposedHeaders = $this->config->exposedHeaders;

        if ($exposedHeaders) {
            $response->setHeader(
                CorsResponseHeaderName::EXPOSE_HEADERS->value,
                implode(', ', $exposedHeaders)
            );
        }

        return $this;
    }
}