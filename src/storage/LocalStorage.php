<?php
// src/storage/LocalStorage.php
// For local XAMPP development: store files in storage/uploads/ and access them through a download script or direct path.

class LocalStorage implements StorageInterface
{
    private string $basePath;
    private string $baseUrl;

    public function __construct()
    {
        $this->basePath = __DIR__ . '/../../storage/uploads';
        $this->baseUrl  = APP_URL . '/uploads'; // A forwarding script in public/ is recommended; see the note below.
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
            // Use copy() as a fallback for non-HTTP uploads such as test scripts.
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
