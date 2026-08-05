<?php
$dotenv = __DIR__ . '/../../.env';
if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[$name] = $value;
        }
    }
}

// Retry connecting 5 times with 2 seconds delay
$tries = 5;
while ($tries > 0) {
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8",
            $_ENV['DB_USERNAME'],
            $_ENV['DB_PASSWORD']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // success
    } catch (PDOException $e) {
        echo "Waiting for database to be ready...\n";
        sleep(2);
        $tries--;
        if ($tries == 0) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
