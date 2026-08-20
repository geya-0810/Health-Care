<?php
// src/storage/S3Storage.php
// Use after migrating to the cloud: composer require aws/aws-sdk-php
// Set these in .env: STORAGE_DRIVER=s3, AWS_REGION, AWS_BUCKET, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY
// (Attaching an IAM Role to the VM is preferred over storing AWS keys in .env; see below.)

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Storage implements StorageInterface
{
    private S3Client $client;
    private string $bucket;

    public function __construct()
    {
        $this->bucket = $_ENV['AWS_BUCKET'] ?? '';

        $config = [
            'version' => 'latest',
            'region'  => $_ENV['AWS_REGION'] ?? 'ap-southeast-1',
        ];

        // Use an access key for local testing; use an IAM Role after deploying to EC2.
        // The SDK then gets temporary credentials from instance metadata, so .env needs no AWS key.
        if (!empty($_ENV['AWS_ACCESS_KEY_ID'] ?? '')) {
            $config['credentials'] = [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ];
        }

        $this->client = new S3Client($config);
    }

    public function upload(string $tmpFilePath, string $destinationKey): string
    {
        try {
            $result = $this->client->putObject([
                'Bucket'     => $this->bucket,
                'Key'        => ltrim($destinationKey, '/'),
                'SourceFile' => $tmpFilePath,
                'ACL'        => 'public-read', // Public images such as doctor avatars; medical attachments should use private + presigned URLs.
            ]);
            return (string) $result['ObjectURL'];
        } catch (AwsException $e) {
            error_log('S3 upload failed: ' . $e->getMessage());
            throw new RuntimeException('File upload to cloud storage failed.');
        }
    }

    public function delete(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => ltrim($key, '/'),
            ]);
            return true;
        } catch (AwsException $e) {
            error_log('S3 delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getUrl(string $key): string
    {
        return $this->client->getObjectUrl($this->bucket, ltrim($key, '/'));
    }
}
