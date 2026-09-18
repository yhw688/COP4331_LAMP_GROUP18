<?php

function getDatabaseConnection()
{
    $host = "DB_HOST";
    $db   = "DB_NAME";
    $user = "DB_USER";
    $pass = "DB_PASSWORD";

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
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