<?php
declare(strict_types=1);

namespace Velo\Logger\Tests;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Velo\Logger\LogTextFormatter;

final class LogTextFormatterTest extends TestCase
{
    private LogTextFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new LogTextFormatter();
    }

    #[Test]
    public function it_does_basic_formatting(): void
    {
        $output = $this->formatter->format('info', 'Test message');

        self::assertStringContainsString('[INFO]', $output);
        self::assertStringContainsString('Test message', $output);
        self::assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}]/', $output);
    }

    #[Test]
    public function it_converts_log_level_to_uppercase(): void
    {
        $output = $this->formatter->format('debug', 'Message');
        self::assertStringContainsString('[DEBUG]', $output);

        $output = $this->formatter->format('warning', 'Message');
        self::assertStringContainsString('[WARNING]', $output);

        $output = $this->formatter->format('error', 'Message');
        self::assertStringContainsString('[ERROR]', $output);

        $output = $this->formatter->format('critical', 'Message');
        self::assertStringContainsString('[CRITICAL]', $output);
    }

    #[Test]
    public function it_interpolates_message_with_context_variables(): void
    {
        $context = [
            'user' => 'John',
            'action' => 'login'
        ];
        $message = 'User {user} performed {action}';

        $output = $this->formatter->format('info', $message, $context);

        self::assertStringContainsString('User John performed login', $output);
        self::assertStringNotContainsString('{user}', $output);
        self::assertStringNotContainsString('{action}', $output);
    }

    #[Test]
    public function it_serializes_context_as_json(): void
    {
        $context = [
            'user_id' => 123,
            'status' => 'active',
            'tags' => ['admin', 'verified']
        ];

        $output = $this->formatter->format('info', 'Test', $context);

        self::assertStringContainsString('"user_id":123', $output);
        self::assertStringContainsString('"status":"active"', $output);
        self::assertStringContainsString('"tags"', $output);
    }

    #[Test]
    public function it_formats_exception_and_includes_in_output(): void
    {
        $exception = new Exception('Test exception', 123);
        $context = ['exception' => $exception];

        $output = $this->formatter->format('error', 'An error occurred', $context);

        self::assertStringContainsString('Stack Trace:', $output);
        self::assertStringContainsString('Exception', $output);
        self::assertStringContainsString('Test exception', $output);
        self::assertStringContainsString($exception->getFile(), $output);
        self::assertStringContainsString((string)$exception->getLine(), $output);
    }

    #[Test]
    public function it_removes_exception_from_context_before_json_serialization(): void
    {
        $exception = new Exception('Test exception');
        $context = [
            'exception' => $exception,
            'user_id' => 456
        ];

        $output = $this->formatter->format('error', 'Error', $context);

        // The context JSON should only contain user_id, not exception
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (str_contains($line, 'user_id')) {
                self::assertStringNotContainsString('exception', $line);
            }
        }
    }

    #[Test]
    public function it_produces_empty_json_for_empty_context(): void
    {
        $output = $this->formatter->format('info', 'Message');

        self::assertStringContainsString('Message', $output);
        // Should not have any JSON context
        self::assertStringNotContainsString('{}', $output);
    }

    #[Test]
    public function it_does_not_interpolate_non_stringable_context_variables(): void
    {
        $context = [
            'array' => [1, 2, 3],
            'object' => new stdClass(),
            'valid' => 'value'
        ];

        $message = 'Array: {array}, Object: {object}, Valid: {valid}';
        $output = $this->formatter->format('info', $message, $context);

        // {array} and {object} should remain as placeholders
        self::assertStringContainsString('{array}', $output);
        self::assertStringContainsString('{object}', $output);
        self::assertStringContainsString('Valid: value', $output);
    }

    #[Test]
    public function it_interpolates_stringable_object_in_context(): void
    {
        $object = new class {
            public function __toString(): string
            {
                return 'StringableObject';
            }
        };

        $context = ['obj' => $object];
        $message = 'Object: {obj}';

        $output = $this->formatter->format('info', $message, $context);

        self::assertStringContainsString('Object: StringableObject', $output);
    }

    #[Test]
    public function it_interpolates_multiple_context_variables(): void
    {
        $context = [
            'host' => 'localhost',
            'port' => 8080,
            'database' => 'myapp'
        ];

        $message = 'Connected to {database} at {host}:{port}';
        $output = $this->formatter->format('info', $message, $context);

        self::assertStringContainsString('Connected to myapp at localhost:8080', $output);
    }

    #[Test]
    public function it_includes_newlines_in_output(): void
    {
        $output = $this->formatter->format('info', 'Test');

        self::assertTrue(str_ends_with($output, "\n\n"));
    }


    #[Test]
    public function it_handles_exceptions_with_newlines_in_message(): void
    {
        $exception = new Exception("Multi\nline\nmessage");
        $context = ['exception' => $exception];

        $output = $this->formatter->format('error', 'Error', $context);

        self::assertStringContainsString('Stack Trace:', $output);
        self::assertStringContainsString('Multi', $output);
    }

    #[Test]
    public function it_includes_milliseconds_in_datetime_format(): void
    {
        $output = $this->formatter->format('info', 'Test');

        self::assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}/', $output);
    }

    #[Test]
    public function it_handles_special_characters_in_message(): void
    {
        $message = 'Special: <>&"\'';
        $output = $this->formatter->format('info', $message);

        self::assertStringContainsString('Special: <>&"\'', $output);
    }

    #[Test]
    public function it_handles_unicode_characters_in_context(): void
    {
        $context = [
            'name' => 'Żółw',
            'city' => 'Москва'
        ];

        $output = $this->formatter->format('info', 'User {name} from {city}', $context);

        self::assertStringContainsString('Żółw', $output);
        self::assertStringContainsString('Москва', $output);
    }

    #[Test]
    public function it_handles_numeric_context_values(): void
    {
        $context = [
            'int' => 42,
            'float' => 3.14,
            'negative' => -100
        ];

        $message = 'Int: {int}, Float: {float}, Negative: {negative}';
        $output = $this->formatter->format('info', $message, $context);

        self::assertStringContainsString('Int: 42', $output);
        self::assertStringContainsString('Float: 3.14', $output);
        self::assertStringContainsString('Negative: -100', $output);
    }

    #[Test]
    public function it_handles_boolean_context_values(): void
    {
        $context = [
            'enabled' => true,
            'disabled' => false
        ];

        $message = 'Enabled: {enabled}, Disabled: {disabled}';
        $output = $this->formatter->format('info', $message, $context);

        self::assertStringContainsString('Enabled: 1', $output);
        self::assertStringContainsString('Disabled: ', $output);
    }

    #[Test]
    public function it_serializes_nested_arrays_in_context_to_json(): void
    {
        $context = [
            'user' => [
                'id' => 1,
                'roles' => ['admin', 'user']
            ]
        ];

        $output = $this->formatter->format('info', 'Test', $context);

        self::assertStringContainsString('"user"', $output);
        self::assertStringContainsString('"id":1', $output);
        self::assertStringContainsString('"roles"', $output);
    }
}