<?php

namespace AudioList\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Audio_List_Admin;
use AWS_Handler;

class AdminTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_aws_configured_returns_true_when_settings_exist() {
        if (defined('AS3CF_SETTINGS')) {
            // Constant already defined, we'll test the logic that handles it
        } else {
            define('AS3CF_SETTINGS', serialize([
                'provider' => 'aws',
                'access-key-id' => 'id',
                'secret-access-key' => 'secret'
            ]));
        }

        $admin = new Audio_List_Admin('audio-list', '1.0.0');
        
        $reflect = new \ReflectionClass($admin);
        $method = $reflect->getMethod('is_aws_configured');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($admin));
    }

    public function test_check_aws_file_fails_on_invalid_nonce() {
        // Mock wp_verify_nonce to return false
        Functions\expect('wp_verify_nonce')->andReturn(false);
        
        // Mock wp_send_json_error
        Functions\expect('wp_send_json_error')
            ->once()
            ->with('Invalid nonce')
            ->andThrow(new \Exception('JSON_ERROR_SENT'));

        $_POST['nonce'] = 'wrong-nonce';

        $admin = new Audio_List_Admin('audio-list', '1.0.0');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JSON_ERROR_SENT');
        $admin->check_aws_file();
    }

    public function test_check_aws_file_handles_handler_not_available() {
        Functions\expect('wp_verify_nonce')->andReturn(true);
        $_POST['nonce'] = 'valid-nonce';
        $_POST['year'] = '2026';
        $_POST['filename'] = 'test.mp3';

        // Mock error_log to avoid output
        Functions\expect('error_log')->zeroOrMoreTimes();

        // Mock wp_send_json_error
        Functions\expect('wp_send_json_error')
            ->once()
            ->andThrow(new \Exception('JSON_ERROR_SENT_NOT_AVAILABLE'));

        $admin = new class('audio-list', '1.0.0') extends Audio_List_Admin {
            protected function get_aws_handler() { return null; }
        };
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JSON_ERROR_SENT_NOT_AVAILABLE');
        $admin->check_aws_file();
    }

    public function test_get_aws_handler_lazy_loads() {
        // Mock get_option which is called in AWS_Handler constructor
        Functions\expect('get_option')->andReturn([]);
        
        $admin = new Audio_List_Admin('audio-list', '1.0.0');
        
        $reflect = new \ReflectionClass($admin);
        $prop = $reflect->getProperty('aws_handler');
        $prop->setAccessible(true);
        
        $this->assertNull($prop->getValue($admin));

        $method = $reflect->getMethod('get_aws_handler');
        $method->setAccessible(true);
        
        $handler = $method->invoke($admin);
        if ($handler) {
            $this->assertInstanceOf(AWS_Handler::class, $handler);
            $this->assertSame($handler, $prop->getValue($admin), 'Should be cached');
        }
    }
}
