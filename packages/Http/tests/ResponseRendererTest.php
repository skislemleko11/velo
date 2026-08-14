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
final class ResponseRendererTest extends TestCase
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
    public function it_renders_json_response_when_no_view_path_provided_and_data_is_array(): void
    {
        $response = HttpResponse::json(['status' => 'success', 'code' => 200]);

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(200)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with(['Content-Type' => 'application/json']);

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
    public function it_renders_plain_text_response_when_no_view_path_provided_and_data_is_not_array(): void
    {
        $response = HttpResponse::plainText('hehe');

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

        $this->assertEquals('hehe', $output);
    }

    #[Test]
    public function it_renders_view_and_passes_session_and_flash_messages(): void
    {
        $tempView = sys_get_temp_dir() . '/test_view_' . uniqid() . '.php';
        file_put_contents(
            $tempView,
            '<?php echo "Hello " . $name . "! Session class: " . get_class($session); ?>'
        );

        $response = HttpResponse::view($tempView, data: ['name' => 'John']);

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
        $response = HttpResponse::redirect('/login', 302);

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