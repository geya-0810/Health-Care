<?php
// src/database/databaseconnect.php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

$server   = $_ENV['DB_HOST']     ?? 'localhost';  // aws RDS Proxy endpoint or localhost
$username = $_ENV['DB_USER']     ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname   = $_ENV['DB_NAME']     ?? 'health_care';
$charset  = 'utf8mb4';

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT         => true,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=$server;charset=$charset", $username, $password, $pdoOptions);
} catch (PDOException $e) {
    error_log('MySQL server connection failed: ' . $e->getMessage());
    die('Unable to connect to database server.');
}

try {
    $dbconnect = new PDO("mysql:host=$server;dbname=$dbname;charset=$charset", $username, $password, $pdoOptions);
    error_log("Connected to database: $dbname");
    return $dbconnect;

} catch (PDOException $e) {

    if (strpos($e->getMessage(), 'Unknown database') !== false) {

        require_once __DIR__ . '/databasecreate.php';

        $created = createDatabase($pdo, $dbname);
        if ($created === 0) {
            try {
                $dbconnect = new PDO("mysql:host=$server;dbname=$dbname;charset=$charset", $username, $password, $pdoOptions);
                error_log("Database '$dbname' created and connected successfully.");
                return $dbconnect;
            } catch (PDOException $e2) {
                error_log('Connection after creation failed: ' . $e2->getMessage());
                die('Database was created but connection failed: ' . $e2->getMessage());
            }
        } else {
            try {
                $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
            } catch (PDOException $e3) {
                error_log('Rollback drop failed: ' . $e3->getMessage());
            }
            die("Database '$dbname' failed to create. Check src/log/php_errors.log for details.");
        }

    } else {
        error_log('Database connection failed: ' . $e->getMessage());
        die('Database connection failed: ' . $e->getMessage());
    }
}