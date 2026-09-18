<?php

header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";

$pdo = getDatabaseConnection();

echo json_encode([
    "status" => "Database connected successfully"
]);