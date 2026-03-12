<?php
declare(strict_types=1);

$host = "127.0.0.1";
$db   = "ietracker";
$user = "root";
$pass = ""; // XAMPP default (local dev)
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
  // Keep MySQL date/time functions (NOW(), CURDATE(), etc.) in Philippine time.
  $pdo->exec("SET time_zone = '+08:00'");
  return $pdo;
} catch (PDOException $e) {
  http_response_code(500);
  echo "Database connection failed.";
  exit;
}