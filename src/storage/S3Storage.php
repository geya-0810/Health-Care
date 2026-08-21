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
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'];

    public function __construct()
    {
        $this->bucket = $_ENV['UPLOADS_DIR'] ?? $_ENV['AWS_BUCKET'] ?? '';
        if ($this->bucket === '') {
            throw new RuntimeException('S3 bucket name is not set — check UPLOADS_DIR in .env');
        }

        $config = [
            'version' => 'latest',
            'region'  => $_ENV['AWS_REGION'] ?? 'us-east-1',
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

    public function upload(string $tmpFilePath, string $destinationKey, bool $public = true): string
    {
        try {
            $result = $this->client->putObject([
                'Bucket'     => $this->bucket,
                'Key'        => ltrim($destinationKey, '/'),
                'SourceFile' => $tmpFilePath,
                'ACL'        => $public ? 'public-read' : 'private',
            ]);
            return (string) $result['ObjectURL'];
        } catch (AwsException $e) {
            error_log('S3 upload failed: ' . $e->getMessage());
            throw new RuntimeException('File upload to cloud storage failed: ' . $e->getAwsErrorMessage());
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

    public function getPresignedUrl(string $key, int $expiresInMinutes = 15): string
    {
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => ltrim($key, '/'),
        ]);
        $request = $this->client->createPresignedRequest($command, "+{$expiresInMinutes} minutes");
        return (string) $request->getUri();
    }

    private function validateFile(string $tmpFilePath, string $destinationKey): void
    {
        if (!is_uploaded_file($tmpFilePath) && !is_readable($tmpFilePath)) {
            throw new RuntimeException('Invalid file upload.');
        }

        $size = filesize($tmpFilePath);
        if ($size === false || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File exceeds the 5MB size limit.');
        }

        $ext = strtolower(pathinfo($destinationKey, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS));
        }
    }
}