<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$config = require __DIR__ . '/config.php';

// If config.php returns the array directly, use $config. 
// If it wraps it under 'database', check for both automatically:
$db = isset($config['database']) ? $config['database'] : $config;

try {
    // Determine database name safely from config or fallback
    $dbname = $db['dbname'] ?? $db['name'] ?? 'university_deals';
    $host   = $db['host']   ?? 'localhost';
    $user   = $db['user']   ?? $db['username'] ?? 'root';
    $pass   = $db['password'] ?? '';

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}