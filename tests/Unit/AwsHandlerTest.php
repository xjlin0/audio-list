<?php

namespace AudioList\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use AWS_Handler;
use AsyncAws\S3\S3Client;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use AsyncAws\Core\Test\Http\SimpleMockHttpClient;

class AwsHandlerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    private function setPrivateProperty($object, $propertyName, $value) {
        $reflection = new \ReflectionClass(AWS_Handler::class);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function test_check_file_exists_returns_true_when_file_exists() {
        // Mock WordPress functions
        Functions\expect('get_option')
            ->andReturn([
                'access-key-id' => 'test-id',
                'secret-access-key' => 'test-secret'
            ]);

        // Use a real S3Client with a MockHttpClient
        $responses = [
            new MockResponse('', ['http_code' => 200])
        ];
        $httpClient = new MockHttpClient($responses);
        $s3 = new S3Client([
            'region' => 'us-west-1',
            'accessKeyId' => 'test-id',
            'accessKeySecret' => 'test-secret'
        ], null, $httpClient);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $this->assertTrue($handler->check_file_exists('2026', 'test.mp3'));
    }

    public function test_check_file_exists_returns_false_when_file_not_found() {
        Functions\expect('get_option')
            ->andReturn([
                'access-key-id' => 'test-id',
                'secret-access-key' => 'test-secret'
            ]);

        $responses = [
            new MockResponse('', ['http_code' => 404])
        ];
        $httpClient = new MockHttpClient($responses);
        $s3 = new S3Client([
            'region' => 'us-west-1',
            'accessKeyId' => 'test-id',
            'accessKeySecret' => 'test-secret'
        ], null, $httpClient);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $this->assertFalse($handler->check_file_exists('2026', 'missing.mp3'));
    }

    public function test_constructor_uses_correct_config_keys() {
        Functions\expect('get_option')
            ->once()
            ->with('aws_settings')
            ->andReturn([
                'access-key-id' => 'my-id',
                'secret-access-key' => 'my-secret'
            ]);

        $handler = new AWS_Handler();
        
        $reflectHandler = new \ReflectionClass(AWS_Handler::class);
        $s3Prop = $reflectHandler->getProperty('s3');
        $s3Prop->setAccessible(true);
        $s3 = $s3Prop->getValue($handler);

        $reflectApi = new \ReflectionClass(\AsyncAws\Core\AbstractApi::class);
        $prop = $reflectApi->getProperty('configuration');
        $prop->setAccessible(true);
        $config = $prop->getValue($s3);
        
        $reflectConfig = new \ReflectionClass($config);
        $userDataProp = $reflectConfig->getProperty('userData');
        $userDataProp->setAccessible(true);
        $userData = $userDataProp->getValue($config);

        $this->assertEquals('my-id', $userData['accessKeyId']);
        $this->assertEquals('my-secret', $userData['accessKeySecret']);
    }

    public function test_upload_file_returns_url_on_success() {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'file content');

        Functions\expect('get_option')->andReturn(['access-key-id' => 'id', 'secret-access-key' => 'secret']);
        Functions\expect('sanitize_file_name')->andReturnUsing(function($name) { return $name; });

        $responses = [
            new MockResponse('', ['http_code' => 200])
        ];
        $httpClient = new MockHttpClient($responses);
        $s3 = new S3Client(['region' => 'us-west-1', 'accessKeyId' => 'id', 'accessKeySecret' => 'secret'], null, $httpClient);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $tempFile
        ];

        $url = $handler->upload_file('2026', $file);
        $this->assertEquals('https://chinese-church.s3.us-west-1.amazonaws.com/restructure_sermon/2026/test.pdf', $url);

        unlink($tempFile);
    }

    public function test_upload_file_throws_exception_on_failure() {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'file content');

        Functions\expect('get_option')->andReturn(['access-key-id' => 'id', 'secret-access-key' => 'secret']);
        Functions\expect('sanitize_file_name')->andReturnUsing(function($name) { return $name; });

        $responses = [
            new MockResponse('', ['http_code' => 500])
        ];
        $httpClient = new MockHttpClient($responses);
        $s3 = new S3Client(['region' => 'us-west-1', 'accessKeyId' => 'id', 'accessKeySecret' => 'secret'], null, $httpClient);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $file = ['name' => 'test.mp3', 'type' => 'audio/mpeg', 'tmp_name' => $tempFile];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Upload failed');
        
        try {
            $handler->upload_file('2026', $file);
        } finally {
            unlink($tempFile);
        }
    }
}
