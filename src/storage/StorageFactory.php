<?php
// src/storage/StorageFactory.php
// Business code uses StorageFactory::make() to get the storage instance for the current environment.
// Set STORAGE_DRIVER=local in .env for local development and STORAGE_DRIVER=s3 in the cloud; no business code changes are needed.

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
