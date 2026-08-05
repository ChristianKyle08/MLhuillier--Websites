<?php
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    $content = "APP_NAME=Cattleya\n";
    $content .= "APP_ENV=development\n";
    $content .= "DB_HOST=db\n";
    $content .= "DB_PORT=3306\n";
    $content .= "DB_DATABASE=cattleya_db\n";
    $content .= "DB_USERNAME=root\n";
    $content .= "DB_PASSWORD=root\n";
    
    file_put_contents($envFile, $content);
    echo ".env file created successfully!\n";
} else {
    echo ".env file already exists.\n";
}