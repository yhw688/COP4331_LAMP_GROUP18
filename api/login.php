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

$login = $data["login"] ?? "";
$password = $data["password"] ?? "";

if (empty($login) || empty($password)) {
    http_response_code(400);

    echo json_encode([
        "error" => "Login and password are required"
    ]);

    exit();
}

$pdo = getDatabaseConnection();

$stmt = $pdo->prepare(
    "SELECT ID, FirstName, LastName, Login, Password, Role, IsEnabled
     FROM Users
     WHERE Login = ?"
);

$stmt->execute([$login]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["Password"])) {

    http_response_code(401);

    echo json_encode([
        "error" => "Invalid username or password"
    ]);

    exit();
}

if (!$user["IsEnabled"]) {
    http_response_code(403);

    echo json_encode([
        "error" => "Account is disabled"
    ]);

    exit();
}

/* Store authenticated user information on the server */
$_SESSION["user_id"] = $user["ID"];
$_SESSION["role"] = $user["Role"];

unset($user["Password"]);
unset($user["IsEnabled"]);

http_response_code(200);

echo json_encode([
    "user" => $user,
    "message" => "Login successful"
]);