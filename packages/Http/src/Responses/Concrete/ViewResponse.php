<?php
declare(strict_types=1);

namespace Velo\Http\Responses\Concrete;

use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\Http\RenderContext;
use Velo\Http\ResponseFormat;
use Velo\Http\Responses\Response;
use Velo\View\ViewResolver\Exceptions\InvalidViewExtensionException;
use Velo\View\ViewResolver\Exceptions\ViewNotFoundException;

/**
 * Represents an HTTP response containing the content of the provided view file.
 */
class ViewResponse extends Response
{
    /**
     * @param string $relativeToViewsDirFilePath Must be a relative path to 'views' directory from PathResolver.
     * @param array $data Will be extracted to variables and passed to the view.
     */
    public function __construct(
        private readonly string $relativeToViewsDirFilePath,
        private readonly array  $data = [],
        int                     $statusCode = 200,
        array                   $headers = []
    )
    {
        $headers[self::CONTENT_TYPE_HEADER] = ResponseFormat::HTML->value . '; charset=utf-8';

        parent::__construct($statusCode, $headers);
    }

    /**
     * @throws ViewNotFoundException
     * @throws PathNotFoundException
     * @throws InvalidViewExtensionException
     */
    public function render(RenderContext $context): string
    {
        return $context->viewRenderer->render($this->relativeToViewsDirFilePath, $this->data);
    }
}