<?php
$config = require __DIR__ . '/config.php';
$db = $config['database'];

try {
    // Notice port=20873 included in the DSN
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Uncomment the line below to test connection success
    // echo "Successfully connected to Clever Cloud MySQL!";
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}