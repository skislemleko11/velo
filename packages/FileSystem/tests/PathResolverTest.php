<?php
declare(strict_types=1);

namespace Velo\FileSystem\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;
use Velo\FileSystem\PathResolver\Exceptions\PathNotFoundException;
use Velo\FileSystem\PathResolver\PathResolver;

final class PathResolverTest extends TestCase
{
    private PathResolver $pathResolver;

    protected function setUp(): void
    {
        $this->pathResolver = new PathResolver();
    }

    #[Test]
    public function it_sets_dir_path(): void
    {
        $key = 'hehe';
        $path = '/hehe/';

        $result = $this->pathResolver->setDirPath($key, $path);

        $this->assertSame($this->pathResolver, $result);
        $this->assertSame($path, $this->pathResolver->getDirPath($key));
    }

    #[Test]
    public function it_sets_file_path(): void
    {
        $key = 'hehe';
        $path = '/views/hehe.php';

        $result = $this->pathResolver->setFilePath($key, $path);

        $this->assertSame($this->pathResolver, $result);
        $this->assertSame($path, $this->pathResolver->getFilePath($key));
    }

    #[Test]
    public function it_gets_dir_path_with_trailing_slash(): void
    {
        $this->pathResolver->setDirPath(
            PathResolver::PUBLIC_DIR_KEY,
            '/public/'
        );

        $this->assertSame(
            '/public/',
            $this->pathResolver->getDirPath(PathResolver::PUBLIC_DIR_KEY)
        );
    }

    #[Test]
    public function it_adds_trailing_slash_to_dir_path(): void
    {
        $this->pathResolver->setDirPath('public', '/public');

        $this->assertSame(
            '/public/',
            $this->pathResolver->getDirPath('public')
        );
    }

    #[Test]
    #[DataProvider('dirPathProvider')]
    public function it_normalizes_trailing_slashes(
        string $path,
        string $expected,
    ): void {
        $this->pathResolver->setDirPath('public', $path);

        $this->assertSame(
            $expected,
            $this->pathResolver->getDirPath('public')
        );
    }

    public static function dirPathProvider(): array
    {
        return [
            'without slash' => ['/public', '/public/'],
            'with one slash' => ['/public/', '/public/'],
            'with multiple slashes' => ['/public////', '/public/'],
            'root directory' => ['/', '/'],
        ];
    }

    #[Test]
    public function it_gets_file_path(): void
    {
        $this->pathResolver->setFilePath('a', '/views/a.php');

        $this->assertSame(
            '/views/a.php',
            $this->pathResolver->getFilePath('a')
        );
    }

    #[Test]
    public function it_throws_type_error_when_setting_null_file_path(): void
    {
        $this->expectException(TypeError::class);

        $this->pathResolver->setFilePath('a', null);
    }

    #[Test]
    public function it_throws_path_not_found_exception_when_getting_unknown_file_path(): void
    {
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessageIs(
            'The requested file path "nonexistent" not found!'
        );

        $this->pathResolver->getFilePath('nonexistent');
    }

    #[Test]
    public function it_throws_path_not_found_exception_when_getting_unknown_dir_path(): void
    {
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessageIs(
            'The requested dir path "nonexistent" not found!'
        );

        $this->pathResolver->getDirPath('nonexistent');
    }

    #[Test]
    public function it_registers_dir_path(): void
    {
        $this->pathResolver->setDirPath('public', '/public');

        $this->assertTrue(
            $this->pathResolver->isDirRegistered('public')
        );
    }

    #[Test]
    public function it_returns_false_when_dir_path_is_not_registered(): void
    {
        $this->assertFalse(
            $this->pathResolver->isDirRegistered('public')
        );
    }

    #[Test]
    public function it_registers_file_path(): void
    {
        $this->pathResolver->setFilePath('view', '/views/view.php');

        $this->assertTrue(
            $this->pathResolver->isFileRegistered('view')
        );
    }

    #[Test]
    public function it_returns_false_when_file_path_is_not_registered(): void
    {
        $this->assertFalse(
            $this->pathResolver->isFileRegistered('view')
        );
    }

    #[Test]
    public function it_sets_and_gets_error_file_path(): void
    {
        $result = $this->pathResolver->setErrorFilePath(
            404,
            'errors/404.php'
        );

        $this->assertSame($this->pathResolver, $result);
        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->getErrorFilePath(404)
        );
    }

    #[Test]
    public function it_sets_and_gets_general_error_file_path(): void
    {
        $result = $this->pathResolver->setErrorGeneralFilePath(
            'errors/general.php'
        );

        $this->assertSame($this->pathResolver, $result);
        $this->assertSame(
            'errors/general.php',
            $this->pathResolver->getErrorGeneralFilePath()
        );
    }

    #[Test]
    public function it_registers_error_file_path(): void
    {
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');

        $this->assertTrue(
            $this->pathResolver->isErrorFileRegistered(404)
        );
    }

    #[Test]
    public function it_returns_false_when_error_file_path_is_not_registered(): void
    {
        $this->assertFalse(
            $this->pathResolver->isErrorFileRegistered(404)
        );
    }

    #[Test]
    public function it_registers_general_error_file_path(): void
    {
        $this->pathResolver->setErrorGeneralFilePath(
            'errors/general.php'
        );

        $this->assertTrue(
            $this->pathResolver->isErrorGeneralFileRegistered()
        );
    }

    #[Test]
    public function it_returns_false_when_general_error_file_path_is_not_registered(): void
    {
        $this->assertFalse(
            $this->pathResolver->isErrorGeneralFileRegistered()
        );
    }

    #[Test]
    public function it_uses_status_code_as_part_of_error_file_key(): void
    {
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');
        $this->pathResolver->setErrorFilePath(500, 'errors/500.php');

        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->getErrorFilePath(404)
        );

        $this->assertSame(
            'errors/500.php',
            $this->pathResolver->getErrorFilePath(500)
        );

        $this->assertTrue(
            $this->pathResolver->isErrorFileRegistered(404)
        );

        $this->assertTrue(
            $this->pathResolver->isErrorFileRegistered(500)
        );
    }

    #[Test]
    public function it_throws_path_not_found_exception_for_unknown_error_file(): void
    {
        $this->expectException(PathNotFoundException::class);

        $this->expectExceptionMessageIsOrContains('404');

        $this->pathResolver->getErrorFilePath(404);
    }

    #[Test]
    public function it_throws_path_not_found_exception_for_unknown_general_error_file(): void
    {
        $this->expectException(PathNotFoundException::class);
        $this->expectExceptionMessageIs(
            'The requested file path "error" not found!'
        );

        $this->pathResolver->getErrorGeneralFilePath();
    }

    #[Test]
    public function it_resolves_specific_error_file_path(): void
    {
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');

        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->resolveErrorFilePath(404)
        );
    }

    #[Test]
    public function it_falls_back_to_general_error_file_path(): void
    {
        $this->pathResolver->setErrorGeneralFilePath('errors/general.php');

        $this->assertSame(
            'errors/general.php',
            $this->pathResolver->resolveErrorFilePath(404)
        );
    }

    #[Test]
    public function it_prefers_specific_error_file_path_over_general_error_file_path(): void
    {
        $this->pathResolver->setErrorGeneralFilePath('errors/general.php');
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');

        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->resolveErrorFilePath(404)
        );
    }

    #[Test]
    public function it_returns_false_when_no_error_file_path_is_registered(): void
    {
        $this->assertFalse(
            $this->pathResolver->resolveErrorFilePath(404)
        );
    }

    #[Test]
    public function it_resolves_error_file_path_for_different_status_codes(): void
    {
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');
        $this->pathResolver->setErrorFilePath(500, 'errors/500.php');

        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->resolveErrorFilePath(404)
        );

        $this->assertSame(
            'errors/500.php',
            $this->pathResolver->resolveErrorFilePath(500)
        );
    }

    #[Test]
    public function it_falls_back_to_general_error_file_path_only_for_unregistered_status_code(): void
    {
        $this->pathResolver->setErrorFilePath(404, 'errors/404.php');
        $this->pathResolver->setErrorGeneralFilePath('errors/general.php');

        $this->assertSame(
            'errors/404.php',
            $this->pathResolver->resolveErrorFilePath(404)
        );

        $this->assertSame(
            'errors/general.php',
            $this->pathResolver->resolveErrorFilePath(500)
        );
    }
}