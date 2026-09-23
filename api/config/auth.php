<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin($pdo)
{
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);

        echo json_encode([
            "error" => "Unauthorized"
        ]);

        exit();
    }

    $stmt = $pdo->prepare(
        "SELECT ID, Role, IsEnabled
         FROM Users
         WHERE ID = ?"
    );

    $stmt->execute([
        $_SESSION["user_id"]
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user["IsEnabled"]) {
        session_unset();
        session_destroy();

        http_response_code(403);

        echo json_encode([
            "error" => "Account is disabled"
        ]);

        exit();
    }

    return $user;
}


function requireAdmin($pdo)
{
    $user = requireLogin($pdo);

    if ($user["Role"] !== "Admin") {
        http_response_code(403);

        echo json_encode([
            "error" => "Admin access required"
        ]);

        exit();
    }

    return $user;
}