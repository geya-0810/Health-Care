<?php
// src/storage/StorageInterface.php

interface StorageInterface
{
    public function upload(string $tmpFilePath, string $destinationKey): string;

    public function delete(string $key): bool;

    public function getUrl(string $key): string;
}
