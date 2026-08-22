<?php
declare(strict_types=1);

namespace Velo\View\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Velo\Session\FlashMessages\Interfaces\FlashMessagesInterface;
use Velo\Session\Session\Interfaces\SessionInterface;
use Velo\View\ViewRenderer;
use Velo\View\ViewResolver\ViewResolver;

final class ViewRendererTest extends TestCase
{
    private ViewRenderer $viewRenderer;
    private ViewResolver $viewResolver;
    private SessionInterface $session;
    private FlashMessagesInterface $flashMessages;
    private string $viewPath = '';

    protected function setUp(): void
    {
        $this->viewResolver = $this->createMock(ViewResolver::class);
        $this->session = $this->createStub(SessionInterface::class);
        $this->flashMessages = $this->createStub(FlashMessagesInterface::class);

        $this->viewRenderer = new ViewRenderer(
            $this->viewResolver,
            $this->session,
            $this->flashMessages
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->viewPath)) {
            unlink($this->viewPath);
        }
    }

    #[Test]
    public function it_renders_html_view(): void
    {
        $this->viewPath = $this->createViewFile('.html', 'hehe');

        $this->viewResolver->expects($this->once())
            ->method('resolve')
            ->with('hehe.html')
            ->willReturn($this->viewPath);

        $result = $this->viewRenderer->render('hehe.html');

        $this->assertSame('hehe', $result);
    }

    #[Test]
    public function it_renders_php_view_with_extracted_data_and_session_and_flash_messages(): void
    {
        $filePath = $this->createViewFile(
            '.php',
            '<?= $name == "Nico" && $session == $s && $flashMessages == $f ? "yes" : "no" ?>'
        );

        $this->viewResolver->expects($this->once())
            ->method('resolve')
            ->with($filePath)
            ->willReturn($filePath);

        $result = $this->viewRenderer->render(
            $filePath,
            ['name' => 'Nico', 's' => $this->session, 'f' => $this->flashMessages]
        );
        $this->assertSame('yes', $result);
    }

    #[Test]
    public function it_does_not_override_either_session_or_flash_messages(): void
    {
        $filePath = $this->createViewFile(
            '.php',
            '<?= is_string($session) || is_string($flashMessages) ? "no" : "yes" ?>'
        );

        $this->viewResolver->expects($this->once())
            ->method('resolve')
            ->with($filePath)
            ->willReturn($filePath);

        $result = $this->viewRenderer->render(
            $filePath,
            ['session' => 's', 'flashMessages' => 'f']
        );

        $this->assertSame('yes', $result);
    }

    private function createViewFile(string $extension, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'view_file') . $extension;

        file_put_contents($path, $content);

        return $path;
    }
}