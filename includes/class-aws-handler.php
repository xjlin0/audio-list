<?php

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class AWS_Handler {
    private $s3;
    private $bucket;

    public function __construct() {
        $aws_settings = get_option('aws_settings') ?: unserialize(constant('AS3CF_SETTINGS'));
        $this->bucket = 'chinese-church';
        
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region'  => 'us-west-1',
            'credentials' => [
                'key'    => $aws_settings['access-key-id'],
                'secret' => $aws_settings['secret-access-key'],
            ],
        ]);
    }

    public function check_file_exists($year, $filename) {
        $key = "restructure_sermon/$year/$filename";
        try {
            return $this->s3->doesObjectExist($this->bucket, $key);
        } catch (AwsException $e) {
            throw new Exception('AWS Error: ' . $e->getAwsErrorMessage());
        }
    }

    public function upload_file($year, $file) {
        $key = "restructure_sermon/$year/" . sanitize_file_name($file['name']);
        
        try {
            $result = $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'SourceFile' => $file['tmp_name'],
                'ACL'    => 'public-read',
            ]);
            return $result['ObjectURL'];
        } catch (Exception $e) {
            throw new Exception('Upload failed: ' . $e->getMessage());
        }
    }
}
