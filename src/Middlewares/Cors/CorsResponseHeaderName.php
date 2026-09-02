<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

enum CorsResponseHeaderName: string
{
	case ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
	case ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';
	case ALLOW_METHODS = 'Access-Control-Allow-Methods';
	case ALLOW_HEADERS = 'Access-Control-Allow-Headers';
	case MAX_AGE = 'Access-Control-Max-Age';
	case EXPOSE_HEADERS = 'Access-Control-Expose-Headers';
}
