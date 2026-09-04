<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete\FileResponse;

use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\FileResponse\Exceptions\FileException;
use Velo\Http\Responses\Response;

/**
 * Represents an HTTP response containing the content of the provided file.
 */
class FileResponse extends Response
{
    public function __construct(
        private readonly string $fullPath,
        int                     $statusCode = 200,
        array                   $headers = []
    )
    {
        parent::__construct($statusCode, $headers);
    }

    /**
     * @throws FileException
     */
    public function render(RenderContext $context): string
    {
        if (!is_file($this->fullPath) || !is_readable($this->fullPath)) {
            throw new FileException("File not found or not readable: $this->fullPath");
        }

        if (($content = file_get_contents($this->fullPath)) === false) {
            throw new FileException("Something went wrong while reading the file: $this->fullPath");
        }

        return $content;
    }
}