<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;

class LogHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_excludes_password_from_logging()
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'name' => 'Test User',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return !isset($data['password']) &&
                       isset($data['email']) &&
                       isset($data['name']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }

    /** @test */
    public function it_excludes_token_from_logging()
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'token' => 'secret-token-123',
            'api_token' => 'api-secret-456',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return !isset($data['token']) &&
                       !isset($data['api_token']) &&
                       isset($data['email']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }

    /** @test */
    public function it_logs_only_allowed_fields_when_specified()
    {
        $request = Request::create('/test', 'POST', [
            'project_id' => 123,
            'project_type' => 'RST',
            'project_title' => 'Test Project',
            'sensitive_data' => 'should not be logged',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return isset($data['project_id']) &&
                       isset($data['project_type']) &&
                       !isset($data['sensitive_data']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request, [
            'project_id',
            'project_type',
        ]);
    }

    /** @test */
    public function it_truncates_long_values()
    {
        $longString = str_repeat('a', 600);
        $request = Request::create('/test', 'POST', [
            'long_field' => $longString,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return isset($data['long_field']) &&
                       strlen($data['long_field']) <= 503 && // 500 + '... (truncated)'
                       str_ends_with($data['long_field'], '... (truncated)');
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }

    /** @test */
    public function it_includes_request_metadata()
    {
        $request = Request::create('/test', 'POST', [
            'field' => 'value',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return isset($data['method']) &&
                       isset($data['url']) &&
                       isset($data['ip']) &&
                       isset($data['user_agent']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }

    /** @test */
    public function it_logs_with_warning_level()
    {
        $request = Request::create('/test', 'POST', [
            'field' => 'value',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request, [], 'warning');
    }

    /** @test */
    public function it_logs_with_error_level()
    {
        $request = Request::create('/test', 'POST', [
            'field' => 'value',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request, [], 'error');
    }

    /** @test */
    public function it_logs_error_with_exception()
    {
        $exception = new \Exception('Test error', 500);
        $request = Request::create('/test', 'POST', [
            'field' => 'value',
        ]);

        Log::shouldReceive('error')
            ->once()
            ->with('Error message', Mockery::on(function ($data) {
                return isset($data['error']) &&
                       isset($data['file']) &&
                       isset($data['line']) &&
                       isset($data['trace']);
            }))
            ->andReturn(true);

        LogHelper::logError('Error message', $exception, $request);
    }

    /** @test */
    public function it_logs_error_without_request()
    {
        $exception = new \Exception('Test error', 500);

        Log::shouldReceive('error')
            ->once()
            ->with('Error message', Mockery::on(function ($data) {
                return isset($data['error']) &&
                       !isset($data['request']);
            }))
            ->andReturn(true);

        LogHelper::logError('Error message', $exception);
    }

    /** @test */
    public function it_gets_project_allowed_fields()
    {
        $fields = LogHelper::getProjectAllowedFields();

        $this->assertIsArray($fields);
        $this->assertContains('project_id', $fields);
        $this->assertContains('project_type', $fields);
        $this->assertContains('project_title', $fields);
    }

    /** @test */
    public function it_gets_report_allowed_fields()
    {
        $fields = LogHelper::getReportAllowedFields();

        $this->assertIsArray($fields);
        $this->assertContains('project_id', $fields);
        $this->assertContains('report_type', $fields);
    }

    /** @test */
    public function it_handles_empty_request()
    {
        $request = Request::create('/test', 'GET');

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return isset($data['method']) &&
                       isset($data['url']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }

    /** @test */
    public function it_handles_request_with_only_sensitive_fields()
    {
        $request = Request::create('/test', 'POST', [
            'password' => 'secret',
            'token' => 'secret-token',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', Mockery::on(function ($data) {
                return !isset($data['password']) &&
                       !isset($data['token']) &&
                       isset($data['method']);
            }))
            ->andReturn(true);

        LogHelper::logSafeRequest('Test message', $request);
    }
}
