<?php
// src/config/config.php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Error logging ----------
error_reporting(E_ALL);
ini_set('display_errors', ($_ENV['APP_DEBUG'] ?? '0') === '1' ? '1' : '0'); 
ini_set('display_startup_errors', ($_ENV['APP_DEBUG'] ?? '0') === '1' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../log/php_errors.log');

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Kuala_Lumpur');

// ---------- App constants ----------
$rawBaseUrl = rtrim($_ENV['BASE_URL'] ?? 'http://localhost/Cloud_Computing/public', '/');
if (!preg_match('#^https?://#i', $rawBaseUrl)) {
    $rawBaseUrl = 'http://' . $rawBaseUrl;
}
define('APP_URL', $rawBaseUrl);
define('STORAGE_DRIVER', $_ENV['STORAGE_DRIVER'] ?? 'local'); 
define('UPLOADS_DIR', $_ENV['UPLOADS_DIR'] ?? 'C:/xampp/htdocs/Cloud%20Computing/storage/'); 

// ---------- Autoload project classes (no Composer PSR-4, simple require map) ----------
require_once __DIR__ . '/../database/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Doctor.php';
require_once __DIR__ . '/../models/Schedule.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/BookingService.php';
require_once __DIR__ . '/../services/MailService.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../storage/StorageInterface.php';
require_once __DIR__ . '/../storage/LocalStorage.php';
require_once __DIR__ . '/../storage/S3Storage.php';
require_once __DIR__ . '/../storage/StorageFactory.php';
