<?php
// src/storage/LocalStorage.php
// XAMPP本地开发用：文件存到 storage/uploads/，通过一个小的下载脚本或直接文件路径访问

class LocalStorage implements StorageInterface
{
    private string $basePath;
    private string $baseUrl;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../storage/uploads';
        $this->baseUrl  = APP_URL . '/uploads'; // 建议public/下放一个转发脚本，见下方说明
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function upload(string $tmpFilePath, string $destinationKey): string
    {
        $target = $this->basePath . '/' . ltrim($destinationKey, '/');
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (!move_uploaded_file($tmpFilePath, $target)) {
            // 非HTTP上传（例如测试脚本）时用 copy() 兜底
            if (!copy($tmpFilePath, $target)) {
                throw new RuntimeException("Failed to store file at $destinationKey");
            }
        }

        return $this->getUrl($destinationKey);
    }

    public function delete(string $key): bool
    {
        $target = $this->basePath . '/' . ltrim($key, '/');
        return file_exists($target) ? unlink($target) : true;
    }

    public function getUrl(string $key): string
    {
        return $this->baseUrl . '/' . ltrim($key, '/');
    }
}
