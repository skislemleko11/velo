<?php
declare(strict_types=1);

namespace Velo\View\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\FileSystem\PathResolver\PathResolver;
use Velo\View\ViewResolver\Exceptions\ViewNotFoundException;
use Velo\View\ViewResolver\ViewResolver;

final class ViewResolverTest extends TestCase
{
    private string $viewsDirectory;

    protected function setUp(): void
    {
        $this->viewsDirectory = sys_get_temp_dir() . '/velo-view-resolver-' . uniqid();

        mkdir($this->viewsDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->viewsDirectory . '/*') as $file) {
            unlink($file);
        }

        rmdir($this->viewsDirectory);
    }

    #[Test]
    public function it_returns_php_view_path_when_view_exists(): void
    {
        $viewName = 'home';
        $viewPath = $this->viewsDirectory . '/' . $viewName . '.php';

        touch($viewPath);

        $pathResolver = $this->createMock(PathResolver::class);

        $pathResolver
            ->expects($this->once())
            ->method('getDirPath')
            ->with('views')
            ->willReturn($this->viewsDirectory . '/');

        $resolver = new ViewResolver($pathResolver);

        $result = $resolver->resolve($viewName);

        $this->assertSame($viewPath, $result);
    }

    #[Test]
    public function it_returns_html_view_path_when_view_exists(): void
    {
        $viewName = 'home';
        $viewPath = $this->viewsDirectory . '/' . $viewName . '.html';

        touch($viewPath);

        $pathResolver = $this->createMock(PathResolver::class);

        $pathResolver
            ->expects($this->once())
            ->method('getDirPath')
            ->with('views')
            ->willReturn($this->viewsDirectory . '/');

        $resolver = new ViewResolver($pathResolver);

        $result = $resolver->resolve($viewName);

        $this->assertSame($viewPath, $result);
    }

    #[Test]
    public function it_throws_exception_when_view_does_not_have_php_or_html_ext(): void
    {
        $viewName = 'home';
        $viewPath = $this->viewsDirectory . '/' . $viewName . '.txt';

        touch($viewPath);

        $pathResolver = $this->createMock(PathResolver::class);

        $pathResolver
            ->expects($this->once())
            ->method('getDirPath')
            ->with('views')
            ->willReturn($this->viewsDirectory . '/');

        $resolver = new ViewResolver($pathResolver);

        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessageIs(
            "The requested view file '$this->viewsDirectory/$viewName.php' or '$this->viewsDirectory/$viewName.html' does not exist.");

        $resolver->resolve($viewName);
    }

    #[Test]
    public function it_throws_exception_when_view_does_not_exist(): void
    {
        $viewName = 'missing';

        $pathResolver = $this->createMock(PathResolver::class);

        $pathResolver
            ->expects($this->once())
            ->method('getDirPath')
            ->with('views')
            ->willReturn($this->viewsDirectory . '/');

        $resolver = new ViewResolver($pathResolver);

        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessageIs(
            "The requested view file '$this->viewsDirectory/$viewName.php' or '$this->viewsDirectory/$viewName.html' does not exist.");

        $resolver->resolve($viewName);
    }
}