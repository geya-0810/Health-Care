<?php
// src/storage/StorageFactory.php
// 业务代码统一用 StorageFactory::make() 拿到当前环境该用的storage实例
// 本地开发 .env 设 STORAGE_DRIVER=local，上云后改成 STORAGE_DRIVER=s3 —— 不用改任何业务代码

class StorageFactory
{
    public static function make(): StorageInterface
    {
        return match (STORAGE_DRIVER) {
            's3'    => new S3Storage(),
            default => new LocalStorage(),
        };
    }
}
