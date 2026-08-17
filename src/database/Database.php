<?php
// src/database/Database.php
// 单例封装：整个请求生命周期只连接一次数据库，models/services统一通过这里拿PDO

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
