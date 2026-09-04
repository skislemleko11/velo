<?php
declare(strict_types=1);

namespace Velo\Http\Tests\Responses\Concrete;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Http\RenderContext;
use Velo\Http\Responses\Concrete\FileResponse\Exceptions\FileException;
use Velo\Http\Responses\Concrete\FileResponse\FileResponse;

final class FileResponseTest extends TestCase
{
    private string $filePath;
    private RenderContext $context;

    protected function setUp(): void
    {
        $this->context = self::createStub(RenderContext::class);

        $this->filePath = tempnam(sys_get_temp_dir(), 'file-response-test-');

        if ($this->filePath === false) {
            self::fail('Could not create temporary file.');
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    #[Test]
    public function it_returns_file_contents(): void
    {
        $content = 'Hello, World!';

        file_put_contents($this->filePath, $content);

        $response = new FileResponse($this->filePath);

        self::assertSame($content, $response->render($this->context));
    }

    #[Test]
    public function it_throws_an_exception_when_the_file_does_not_exist(): void
    {
        $filePath = sys_get_temp_dir() . '/non-existent-file-' . uniqid();

        $response = new FileResponse($filePath);

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(
            "File not found or not readable: $filePath"
        );

        $response->render($this->context);
    }

    #[Test]
    public function it_throws_an_exception_when_the_path_is_not_a_file(): void
    {
        $directoryPath = sys_get_temp_dir();

        $response = new FileResponse($directoryPath);

        $this->expectException(FileException::class);
        $this->expectExceptionMessageIs(
            "File not found or not readable: $directoryPath"
        );

        $response->render($this->context);
    }
}