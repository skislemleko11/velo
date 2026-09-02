<?php
declare(strict_types=1);

namespace Velo\Middlewares\Cors;

enum CorsRequestHeaderName: string
{
	case ORIGIN = 'origin';
	case REQUEST_METHOD = 'access-control-request-method';
	case REQUEST_HEADERS = 'access-control-request-headers';
}