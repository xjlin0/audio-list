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

    public function test_check_file_exists_returns_true_when_file_exists() {
        // Mock WordPress functions
        Functions\expect('get_option')
            ->with('aws_settings')
            ->andReturn([
                'access-key-id' => 'test-id',
                'secret-access-key' => 'test-secret'
            ]);

        // Mock S3Client
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock(HeadObjectOutput::class);

        $s3Mock->shouldReceive('headObject')
            ->once()
            ->with([
                'Bucket' => 'chinese-church',
                'Key' => 'restructure_sermon/2026/test.mp3'
            ])
            ->andReturn($resultMock);

        $resultMock->shouldReceive('resolve')
            ->once()
            ->andReturn(true);

        // Inject mock S3Client
        $handler = new class($s3Mock) extends AWS_Handler {
            public function __construct($s3) {
                // We bypass the original constructor for testing
                $this->s3 = $s3;
                $this->bucket = 'chinese-church';
            }
            // Expose properties for injection
            public $s3;
            public $bucket;
        };

        $this->assertTrue($handler->check_file_exists('2026', 'test.mp3'));
    }

    public function test_check_file_exists_returns_false_when_file_not_found() {
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock(HeadObjectOutput::class);

        $s3Mock->shouldReceive('headObject')
            ->andReturn($resultMock);

        $resultMock->shouldReceive('resolve')
            ->andThrow(Mockery::mock(NoSuchKeyException::class));

        $handler = new class($s3Mock) extends AWS_Handler {
            public function __construct($s3) {
                $this->s3 = $s3;
                $this->bucket = 'chinese-church';
            }
            public $s3;
            public $bucket;
        };

        $this->assertFalse($handler->check_file_exists('2026', 'missing.mp3'));
    }

    public function test_constructor_uses_correct_config_keys() {
        // We want to verify that the handler correctly maps 'secret-access-key' from settings
        // to 'accessKeySecret' for AsyncAws.
        
        Functions\expect('get_option')
            ->once()
            ->with('aws_settings')
            ->andReturn([
                'access-key-id' => 'my-id',
                'secret-access-key' => 'my-secret'
            ]);

        // We use a helper class to inspect the internal state after construction
        $handler = new class extends AWS_Handler {
            public function __construct() {
                parent::__construct();
            }
            public function getS3Config() {
                // This is a bit of a hack to test the private/protected state 
                // but necessary to verify the constructor logic.
                // In AsyncAws, the configuration is stored inside the client.
                $reflect = new \ReflectionClass($this->s3);
                $prop = $reflect->getProperty('configuration');
                $prop->setAccessible(true);
                $config = $prop->getValue($this->s3);
                
                $reflectConfig = new \ReflectionClass($config);
                $userDataProp = $reflectConfig->getProperty('userData');
                $userDataProp->setAccessible(true);
                return $userDataProp->getValue($config);
            }
        };

        $config = $handler->getS3Config();
        $this->assertEquals('my-id', $config['accessKeyId']);
        $this->assertEquals('my-secret', $config['accessKeySecret']);
        $this->assertArrayNotHasKey('secretAccessKey', $config, 'Should NOT use secretAccessKey');
    }

    public function test_upload_file_returns_url_on_success() {
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock(\AsyncAws\S3\Result\PutObjectOutput::class);

        $file = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpabc123'
        ];

        // Mock file_get_contents
        Functions\when('file_get_contents')->justReturn('file content');
        Functions\when('sanitize_file_name')->alias('basename');

        $s3Mock->shouldReceive('putObject')
            ->once()
            ->with(Mockery::on(function($params) {
                return $params['Bucket'] === 'chinese-church' &&
                       $params['Key'] === 'restructure_sermon/2026/test.pdf' &&
                       $params['ContentType'] === 'application/pdf' &&
                       $params['ContentDisposition'] === 'inline';
            }))
            ->andReturn($resultMock);

        $resultMock->shouldReceive('resolve')->once();

        $handler = new class($s3Mock) extends AWS_Handler {
            public function __construct($s3) {
                $this->s3 = $s3;
                $this->bucket = 'chinese-church';
            }
            public $s3;
            public $bucket;
        };

        $url = $handler->upload_file('2026', $file);
        $this->assertEquals('https://chinese-church.s3.us-west-1.amazonaws.com/restructure_sermon/2026/test.pdf', $url);
    }

    public function test_upload_file_throws_exception_on_failure() {
        $s3Mock = Mockery::mock(S3Client::class);
        $resultMock = Mockery::mock(\AsyncAws\S3\Result\PutObjectOutput::class);

        $file = ['name' => 'test.mp3', 'type' => 'audio/mpeg', 'tmp_name' => '/tmp/123'];
        
        Functions\when('file_get_contents')->justReturn('content');
        Functions\when('sanitize_file_name')->alias('basename');

        $s3Mock->shouldReceive('putObject')->andReturn($resultMock);
        $resultMock->shouldReceive('resolve')->andThrow(Mockery::mock(AsyncAwsException::class));

        $handler = new class($s3Mock) extends AWS_Handler {
            public function __construct($s3) { $this->s3 = $s3; $this->bucket = 'chinese-church'; }
            public $s3;
            public $bucket;
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Upload failed');
        $handler->upload_file('2026', $file);
    }
}
