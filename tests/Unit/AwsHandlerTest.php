<?php

namespace AudioList\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use AWS_Handler;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Exception\NoSuchKeyException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AwsHandlerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function setPrivateProperty($object, $propertyName, $value) {
        $reflection = new \ReflectionClass(AWS_Handler::class);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function test_check_file_exists_returns_true_when_file_exists() {
        Functions\expect('get_option')
            ->with('aws_settings')
            ->andReturn([
                'access-key-id' => 'test-id',
                'secret-access-key' => 'test-secret'
            ]);

        $s3Mock = Mockery::mock(S3Client::class);
        // Use a generic mock to avoid 'final' method issues on real Result objects
        $resultMock = Mockery::mock('OverloadResult');
        $resultMock->shouldReceive('resolve')->once()->andReturn(true);

        $s3Mock->shouldReceive('headObject')
            ->once()
            ->andReturn($resultMock);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3Mock);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $this->assertTrue($handler->check_file_exists('2026', 'test.mp3'));
    }

    public function test_check_file_exists_returns_false_when_file_not_found() {
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock('OverloadResult2');

        $s3Mock->shouldReceive('headObject')
            ->andReturn($resultMock);

        // We can't easily instantiate NoSuchKeyException because it's final and has complex deps.
        // But we can mock the resolve() call to throw it.
        // However, Mockery can't mock resolve() because it's final.
        // So we make headObject itself throw, which achieves the same catch-block entry.
        
        $responseMock = Mockery::mock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
        $responseMock->shouldReceive('getInfo')->andReturn(404);
        $exception = new NoSuchKeyException($responseMock);

        $resultMock->shouldReceive('resolve')
            ->andThrow($exception);

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3Mock);
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

        // configuration is defined in AbstractApi
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
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock('OverloadResult3');

        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpabc123'
        ];

        Functions\expect('file_get_contents')
            ->andReturn('file content');

        Functions\expect('sanitize_file_name')
            ->andReturnUsing(function($name) { return $name; });

        $s3Mock->shouldReceive('putObject')
            ->once()
            ->andReturn($resultMock);

        $resultMock->shouldReceive('resolve')->once();

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3Mock);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $url = $handler->upload_file('2026', $file);
        $this->assertEquals('https://chinese-church.s3.us-west-1.amazonaws.com/restructure_sermon/2026/test.pdf', $url);
    }

    public function test_upload_file_throws_exception_on_failure() {
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock('OverloadResult4');

        $file = ['name' => 'test.mp3', 'type' => 'audio/mpeg', 'tmp_name' => '/tmp/123'];
        
        Functions\expect('file_get_contents')
            ->andReturn('content');

        Functions\expect('sanitize_file_name')
            ->andReturnUsing(function($name) { return $name; });

        $s3Mock->shouldReceive('putObject')->andReturn($resultMock);
        $resultMock->shouldReceive('resolve')->andThrow(new \Exception('Upload failed'));

        $handler = Mockery::mock(AWS_Handler::class)->makePartial();
        $this->setPrivateProperty($handler, 's3', $s3Mock);
        $this->setPrivateProperty($handler, 'bucket', 'chinese-church');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Upload failed');
        $handler->upload_file('2026', $file);
    }
}
