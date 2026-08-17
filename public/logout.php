<?php
// public/logout.php
require_once __DIR__ . '/../src/config/config.php';

(new AuthService(Database::getConnection()))->logout();

header('Location: login.php?logged_out=1');
exit;
