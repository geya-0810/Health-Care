<?php
// src/database/Database.php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = require __DIR__ . '/databaseconnect.php';
        }
        return self::$instance;
    }
}
