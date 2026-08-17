<?php
// src/storage/StorageInterface.php
// 业务代码只依赖这个接口，永远不直接碰 LocalStorage / S3Storage

interface StorageInterface
{
    /**
     * 上传文件，返回可公开访问的URL（或本地路径）
     */
    public function upload(string $tmpFilePath, string $destinationKey): string;

    public function delete(string $key): bool;

    public function getUrl(string $key): string;
}
