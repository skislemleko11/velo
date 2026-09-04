<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete\FileResponse\Exceptions;

use Exception;
use Velo\Http\Exceptions\Interfaces\HttpExceptionInterface;

class FileException extends Exception implements HttpExceptionInterface
{

}