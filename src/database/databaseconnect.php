<?php
// src/database/databaseconnect.php

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeload();

$server = $_ENV['DB_HOST'] ?? 'localhost';       
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'health_care';
$charset = "utf8mb4";

try{
	$pdo = new PDO("mysql:host=$server;charset=$charset", $username, $password);
} catch (Exception $e){
	die ("Unable to connect to localhost");
}

try {
	$dbconnect = new PDO("mysql:host=$server;dbname=$dbname;charset=$charset", $username, $password);
	$dbconnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	error_log("Connected to database: $dbname");
	// echo "Database successfully connected";
	return ($dbconnect);
	exit();
} catch (PDOException $e) {
	if (strpos($e, "Unknown database")){
		include (__DIR__ . '/databasecreate.php');
		if (!createDatabase($pdo, $dbname))
		{
			//echo "Database successfully created<br><br>";
			$dbconnect = new PDO("mysql:host=$server;dbname=$dbname;charset=$charset", $username, $password);
			return($dbconnect);
			exit();
			// if (!$dbconnect)
			// 	echo "Database $dbname failed to connect<br>";
			// else
			// 	echo "Database $dbname successfully connected<br>";
		}
		else
		{
			try {
				$dbconnect = new PDO("mysql:host=$server;dbname=$dbname;charset=$charset", $username, $password);
				if ($dbconnect)
					$dbconnect->query("DROP DATABASE $dbname");
			} catch(Exception $e){
				// echo "Database has not been created<br>";
			}
			die("Database failed to create:" . $e->getMessage());
		}
	}
	else {
        error_log("Database connection failed: " . $e->getMessage());
        //die("Database connection failed.");
    	die("Database connection failed: " . $e->getMessage());
    }
}
?>