<?php

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Enum\ObjectCannedACL;
use AsyncAws\S3\Exception\NoSuchKeyException;
use AsyncAws\Core\Exception\Exception as AsyncAwsException;

class AWS_Handler {
    private $s3;
    private $bucket;

    public function __construct() {
        $settings = defined('AS3CF_SETTINGS') ? AS3CF_SETTINGS : null;
        $aws_settings = get_option('aws_settings') ?: (is_array($settings) ? $settings : unserialize($settings));
        $this->bucket = 'chinese-church';
        
        $this->s3 = new S3Client([
            'region'  => 'us-west-1',
            'accessKeyId' => $aws_settings['access-key-id'],
            'accessKeySecret' => $aws_settings['secret-access-key'],
        ]);
    }

    public function check_file_exists($year, $filename) {
        $key = "restructure_sermon/$year/$filename";
        try {
            $this->s3->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ])->resolve();
            return true;
        } catch (NoSuchKeyException $e) {
            return false;
        } catch (AsyncAwsException $e) {
            throw new Exception('AWS Error: ' . $e->getMessage());
        }
    }

    public function upload_file($year, $file) {
        $key = "restructure_sermon/$year/" . sanitize_file_name($file['name']);

        // Determine Content-Type and Content-Disposition
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $contentType = $file['type'] ?? 'application/octet-stream';

        // Read the file content
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            throw new Exception('Failed to read temporary file.');
        }

        // For PDF files, set Content-Disposition to inline to display in browser
        $uploadParams = [
            'Bucket' => $this->bucket,
            'Key'    => $key,
            'Body'   => $content,
            'ACL'    => ObjectCannedACL::PUBLIC_READ,
            'ContentType' => $contentType,
        ];

        if ($extension === 'pdf') {
            $uploadParams['ContentDisposition'] = 'inline';
        }

        try {
            $result = $this->s3->putObject($uploadParams);
            $result->resolve();
            // Construct the ObjectURL manually as putObject in AsyncAws doesn't return it directly in the same way
            return sprintf('https://%s.s3.%s.amazonaws.com/%s', $this->bucket, 'us-west-1', $key);
        } catch (AsyncAwsException $e) {
            throw new Exception('Upload failed: ' . $e->getMessage());
        }
    }
}
