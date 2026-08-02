<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Velo\Http\Emitter\Interfaces\EmitterInterface;
use Velo\Http\HttpResponse;
use Velo\Http\ResponseRenderer;
use Velo\Session\FlashMessages\Interfaces\FlashMessagesInterface;
use Velo\Session\Session\Interfaces\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
class ResponseRendererTest extends TestCase
{
    private SessionInterface $sessionMock;
    private EmitterInterface $emitterMock;
    private ResponseRenderer $renderer;

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $flashMessagesMock = $this->createMock(FlashMessagesInterface::class);
        $this->emitterMock = $this->createMock(EmitterInterface::class);

        $this->renderer = new ResponseRenderer(
            emitter: $this->emitterMock,
            session: $this->sessionMock,
            flashMessages: $flashMessagesMock
        );
    }

    #[Test]
    public function it_renders_json_response_when_no_view_path_provided(): void
    {
        $response = new HttpResponse(
            statusCode: 200,
            data: ['status' => 'success', 'code' => 200]
        );

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeader')
            ->with('Content-Type', 'application/json');

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();
        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertEquals('Terminated', $e->getMessage());
        }
        $output = ob_get_clean();

        $this->assertJsonStringEqualsJsonString(
            json_encode(['status' => 'success', 'code' => 200]),
            $output
        );
    }

    #[Test]
    public function it_renders_view_and_passes_session_and_flash_messages(): void
    {
        $tempView = sys_get_temp_dir() . '/test_view_' . uniqid() . '.php';
        file_put_contents(
            $tempView,
            '<?php echo "Hello " . $name . "! Session class: " . get_class($session); ?>'
        );

        $response = new HttpResponse(
            viewPath: $tempView,
            data: ['name' => 'John']
        );

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();
        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertEquals('Terminated', $e->getMessage());
        }
        $output = ob_get_clean();

        unlink($tempView);

        $this->assertStringContainsString('Hello John!', $output);
        $this->assertStringContainsString(get_class($this->sessionMock), $output);
    }

    #[Test]
    public function it_terminates_immediately_on_redirect_header(): void
    {
        $response = new HttpResponse(
            statusCode: 302,
            headers: ['Location' => '/login']
        );

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertEquals('Terminated', $e->getMessage());
        }
    }
}