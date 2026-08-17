<?php
// src/storage/S3Storage.php
// 迁移云端后用：composer require aws/aws-sdk-php
// .env 需要设置：STORAGE_DRIVER=s3, AWS_REGION, AWS_BUCKET, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY
// (更推荐给VM挂IAM Role，不把AWS key写进.env —— 见下方说明)

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

        // 本地测试用access key；部署到EC2后建议改用IAM Role，
        // 这样SDK会自动从instance metadata拿临时凭证，.env里就不用放AWS key了
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
                'ACL'        => 'public-read', // 医生头像等公开图片；病历附件建议改 private + presigned URL
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
