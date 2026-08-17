<?php
declare(strict_types=1);

namespace Velo\Http\Tests;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Velo\Http\Emitter\Interfaces\EmitterInterface;
use Velo\Http\HttpResponse;
use Velo\Http\RequestMethod;
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
        $response = HttpResponse::json([
            'status' => 'success',
            'code' => 200,
        ]);

        $expectedContent = json_encode([
            'status' => 'success',
            'code' => 200,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(200)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'application/json',
                'Content-Length' => (string) strlen($expectedContent),
            ]);

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();

        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertSame('Terminated', $e->getMessage());
        }

        $output = ob_get_clean();

        $this->assertJsonStringEqualsJsonString(
            $expectedContent,
            $output
        );
    }

    #[Test]
    public function it_renders_plain_text_response_when_no_view_path_provided_and_data_is_not_array(): void
    {
        $response = HttpResponse::plainText('hehe');

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with($response->statusCode)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'text/plain',
                'Content-Length' => '4',
            ]);

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();

        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertSame('Terminated', $e->getMessage());
        }

        $output = ob_get_clean();

        $this->assertSame('hehe', $output);
    }

    #[Test]
    public function it_renders_view_and_passes_session_and_flash_messages(): void
    {
        $tempView = sys_get_temp_dir() . '/test_view_' . uniqid() . '.php';

        file_put_contents(
            $tempView,
            '<?php echo "Hello " . $name . "! Session class: " . get_class($session); ?>'
        );

        try {
            $response = HttpResponse::view(
                $tempView,
                data: ['name' => 'John']
            );

            $expectedContent = 'Hello John! Session class: ' . get_class($this->sessionMock);

            $this->emitterMock
                ->expects($this->once())
                ->method('setStatusCode')
                ->with($response->statusCode)
                ->willReturnSelf();

            $this->emitterMock
                ->expects($this->once())
                ->method('sendHeaders')
                ->with($this->callback(
                    static function (array $headers) use ($expectedContent): bool {
                        return ($headers['Content-Length'] ?? null)
                            === (string) strlen($expectedContent);
                    }
                ));

            $this->emitterMock
                ->expects($this->once())
                ->method('terminate')
                ->willThrowException(new RuntimeException('Terminated'));

            ob_start();

            try {
                $this->renderer->render($response);
            } catch (RuntimeException $e) {
                $this->assertSame('Terminated', $e->getMessage());
            }

            $output = ob_get_clean();

            $this->assertSame($expectedContent, $output);
        } finally {
            if (file_exists($tempView)) {
                unlink($tempView);
            }
        }
    }

    #[Test]
    public function it_does_not_render_content_on_redirect(): void
    {
        $response = HttpResponse::redirect('/login');

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with($response->statusCode)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with([
                'Location' => '/login',
                'Content-Length' => '0',
            ]);

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();

        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertSame('Terminated', $e->getMessage());
        }

        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    #[Test]
    public function it_sends_content_length_but_does_not_render_body_for_head_request(): void
    {
        $response = HttpResponse::plainText('hehe');

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with($response->statusCode)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'text/plain',
                'Content-Length' => '4',
            ]);

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();

        try {
            $this->renderer->render($response, RequestMethod::HEAD);
        } catch (RuntimeException $e) {
            $this->assertSame('Terminated', $e->getMessage());
        }

        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    #[Test]
    public function it_does_not_override_existing_content_length_header(): void
    {
        $response = HttpResponse::plainText('hehe');
        $response->setHeader('Content-Length', '999');

        $this->emitterMock
            ->expects($this->once())
            ->method('setStatusCode')
            ->with($response->statusCode)
            ->willReturnSelf();

        $this->emitterMock
            ->expects($this->once())
            ->method('sendHeaders')
            ->with([
                'Content-Type' => 'text/plain',
                'Content-Length' => '999',
            ]);

        $this->emitterMock
            ->expects($this->once())
            ->method('terminate')
            ->willThrowException(new RuntimeException('Terminated'));

        ob_start();

        try {
            $this->renderer->render($response);
        } catch (RuntimeException $e) {
            $this->assertSame('Terminated', $e->getMessage());
        }

        $output = ob_get_clean();

        $this->assertSame('hehe', $output);
    }
}