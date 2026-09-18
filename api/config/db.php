<?php

function loadEnv($path)
{
    if (!file_exists($path)) {
        throw new Exception(".env file not found");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), "#")) {
            continue;
        }

        [$key, $value] = explode("=", $line, 2);

        $_ENV[trim($key)] = trim($value);
    }
}

# PDO is PHP’s database interface. We use it to connect to MySQL and execute prepared statements
function getDatabaseConnection()
{
    loadEnv(__DIR__ . "/../../.env");

    $host = $_ENV["DB_HOST"];
    $db   = $_ENV["DB_NAME"];
    $user = $_ENV["DB_USER"];
    $pass = $_ENV["DB_PASSWORD"];
    $port = $_ENV["DB_PORT"] ?? "3306";
    $charset = $_ENV["DB_CHARSET"] ?? "utf8mb4";

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=$charset",
            $user,
            $pass
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        return $pdo;

    } catch (PDOException $e) {

        http_response_code(500);

        echo json_encode([
            "error" => "Database connection failed"
        ]);

        exit();
    }
}