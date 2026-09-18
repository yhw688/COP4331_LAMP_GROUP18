<?php

header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "error" => "Method not allowed"
    ]);

    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$firstName = $data["firstName"] ?? "";
$lastName  = $data["lastName"] ?? "";
$login     = $data["login"] ?? "";
$password  = $data["password"] ?? "";

if (
    empty($firstName) ||
    empty($lastName) ||
    empty($login) ||
    empty($password)
) {
    http_response_code(400);

    echo json_encode([
        "error" => "All fields are required"
    ]);

    exit();
}

$pdo = getDatabaseConnection();

$stmt = $pdo->prepare(
    "SELECT ID
     FROM Users
     WHERE Login = ?"
);

$stmt->execute([$login]);

if ($stmt->fetch()) {

    http_response_code(409);

    echo json_encode([
        "error" => "Username already exists"
    ]);

    exit();
}

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "INSERT INTO Users
    (FirstName, LastName, Login, Password, DateCreated, DateUpdated)
    VALUES (?, ?, ?, ?, NOW(), NOW())"
);

$stmt->execute([
    $firstName,
    $lastName,
    $login,
    $passwordHash
]);

http_response_code(201);

echo json_encode([
    "message" => "User registered successfully",
    "id" => $pdo->lastInsertId()
]);