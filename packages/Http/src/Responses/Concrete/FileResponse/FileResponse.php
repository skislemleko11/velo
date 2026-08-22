<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete\FileResponse;

use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\FileResponse\Exceptions\FileNotFoundOrNotReadableException;
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
     * @throws FileNotFoundOrNotReadableException
     */
    public function render(RenderContext $context): string
    {
        if (!is_file($this->fullPath) || !is_readable($this->fullPath)) {
            throw new FileNotFoundOrNotReadableException("File not found or not readable: $this->fullPath");
        }

        return file_get_contents($this->fullPath);
    }
}