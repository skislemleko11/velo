<?php
declare(strict_types=1);

namespace Velo\Tests\Controllers;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Controllers\Controller;
use Velo\Http\HttpResponse;
use Velo\Router\Exceptions\PageNotFoundException;
use Velo\Router\PathResolver;

#[AllowMockObjectsWithoutExpectations]
class ControllerTest extends TestCase
{
    protected PathResolver $pathResolverMock;
    protected object $controller;

    protected function setUp(): void
    {
        $this->pathResolverMock = $this->createMock(PathResolver::class);
        $this->pathResolverMock->method('getDirPath')
            ->willReturn(__DIR__ . '/');
        $this->controller = new class($this->pathResolverMock) extends Controller {
            public function triggerResponse(?string $view, int $statusCode = 200, array $data = []): HttpResponse
            {
                return $this->returnResopnse($view, $data, $statusCode);
            }
        };
    }

    #[Test]
    public function it_returns_HttpResponse_with_null_path_when_no_view_provided(): void
    {
        $data = ['key' => 'value'];
        $response = $this->controller->triggerResponse(null, 200, $data);

        $this->assertSame(200, $response->statusCode);
        $this->assertSame(null, $response->viewPath);
        $this->assertSame($data, $response->data);
    }

    #[Test]
    public function it_returns_HttpResponse_with_view_path_when_view_file_exists(): void
    {
        $fileName = 'testfile_controller';
        $filePath = __DIR__ . '/' . $fileName . '.php';
        file_put_contents($filePath, '');
        $data = ['key' => 'value'];

        $response = $this->controller->triggerResponse($fileName, 200, $data);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame($filePath, $response->viewPath);
        $this->assertSame($data, $response->data);

        unlink($filePath);
    }

    #[Test]
    public function it_throws_exception_when_file_does_not_exist(): void
    {
        $this->pathResolverMock->expects($this->once())
            ->method('getDirPath')
            ->with('views')
            ->willReturn('/fake/path/');

        $this->expectException(PageNotFoundException::class);
        $this->controller->triggerResponse('non-existing-view');
    }
}