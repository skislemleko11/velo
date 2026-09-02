<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors\CorsConfig;

use Velo\Http\RequestMethod;
use Velo\Middlewares\Cors\CorsConfig\Exceptions\InvalidConfigurationException;

final readonly class CorsConfig
{
    public bool $allowAllOrigins;
    public bool $allowAllHeaders;
    public bool $exposeAllHeaders;
    public int $maxAgeSeconds;

    /**
     * @var array $allowedHeaders Trimmed lowercase headers.
     */
    public array $allowedHeaders;

    /**
     * @var array $exposedHeaders Trimmed lowercase headers.
     */
    public array $exposedHeaders;

    /**
     * @param list<string> $allowedOrigins To make all origins allowed pass ['*'],
     * you cannot do this when $allowCredentials is true.
     * @param list<RequestMethod> $allowedMethods
     * @param list<string> $allowedHeaders Will be converted to lowercase and trimmed.
     * @param list<string> $exposedHeaders Will be converted to lowercase and trimmed.
     * @param bool $allowCredentials Cannot be true when all origins are allowed.
     *
     * @throws InvalidConfigurationException Thrown when all origins are allowed and tried to set allowCredentials to true.
     */
    public function __construct(
        public array $allowedOrigins = ['*'],
        public array $allowedMethods = [
            RequestMethod::GET,
            RequestMethod::HEAD,
            RequestMethod::POST,
            RequestMethod::PUT,
            RequestMethod::PATCH,
            RequestMethod::DELETE,
            RequestMethod::QUERY,
            RequestMethod::OPTIONS
        ],
        array        $allowedHeaders = [],
        array        $exposedHeaders = [],
        public bool  $allowCredentials = false,
        int          $maxAgeSeconds = 5
    )
    {
        $this->allowedHeaders = $this->normalizeHeaders($allowedHeaders);
        $this->exposedHeaders = $this->normalizeHeaders($exposedHeaders);

        $this->allowAllOrigins = $this->hasWildcard($this->allowedOrigins);
        $this->allowAllHeaders = $this->hasWildcard($this->allowedHeaders);
        $this->exposeAllHeaders = $this->hasWildcard($this->exposedHeaders);

        $this->maxAgeSeconds = max(0, $maxAgeSeconds);

        if ($this->allowCredentials) {
            if ($this->allowAllOrigins) {
                $this->throwInvalidConfigurationException('all origins are allowed.');
            }

            if ($this->allowAllHeaders) {
                $this->throwInvalidConfigurationException('all headers are allowed.');
            }

            if ($this->exposeAllHeaders) {
                $this->throwInvalidConfigurationException('all headers are exposed.');
            }
        }
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(['Velo\Http\HeadersUtils', 'makeLowerCaseAndTrim'], $headers);
    }

    private function hasWildcard(array $headers): bool
    {
        return in_array('*', $headers, true);
    }

    private function throwInvalidConfigurationException(string $reason): never
    {
        throw new InvalidConfigurationException(
            'Cannot set $allowCredentials constructor parameter to true when ' . $reason
        );
    }
}