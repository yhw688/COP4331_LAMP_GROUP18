<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";

$pdo = getDatabaseConnection();
$admin = requireAdmin($pdo);

$method = $_SERVER["REQUEST_METHOD"];

try {

    // ----------------------------------------------------
    // GET - Search/list users
    // ----------------------------------------------------
    if ($method === "GET") {

        $search = trim($_GET["q"] ?? "");
        $searchTerm = "%" . $search . "%";

        $stmt = $pdo->prepare(
            "SELECT ID,
                    FirstName,
                    LastName,
                    Login,
                    Role,
                    IsEnabled,
                    DateCreated,
                    DateUpdated
             FROM Users
             WHERE FirstName LIKE ?
                OR LastName LIKE ?
                OR Login LIKE ?
                OR Role LIKE ?
             ORDER BY LastName, FirstName
             LIMIT 50"
        );

        $stmt->execute([
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm
        ]);

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);

        echo json_encode([
            "users" => $users
        ]);

        exit();
    }


    // ----------------------------------------------------
    // POST - Create another Admin
    // ----------------------------------------------------
    if ($method === "POST") {

        $data = json_decode(
            file_get_contents("php://input"),
            true
        ) ?? [];

        $firstName = trim($data["firstName"] ?? "");
        $lastName  = trim($data["lastName"] ?? "");
        $login     = trim($data["login"] ?? "");
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

        // Check for duplicate login
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
             (FirstName,
              LastName,
              Login,
              Password,
              DateCreated,
              DateUpdated,
              Role,
              IsEnabled)
             VALUES (?, ?, ?, ?, NOW(), NOW(), 'Admin', 1)"
        );

        $stmt->execute([
            $firstName,
            $lastName,
            $login,
            $passwordHash
        ]);

        http_response_code(201);

        echo json_encode([
            "message" => "Admin created successfully",
            "id" => $pdo->lastInsertId()
        ]);

        exit();
    }


    // ----------------------------------------------------
    // PUT - Manage existing user
    // ----------------------------------------------------
    if ($method === "PUT") {

        if (!isset($_GET["id"])) {
            http_response_code(400);

            echo json_encode([
                "error" => "User ID is required"
            ]);

            exit();
        }

        $userId = (int) $_GET["id"];

        // First make sure the user actually exists
        $stmt = $pdo->prepare(
            "SELECT ID
             FROM Users
             WHERE ID = ?"
        );

        $stmt->execute([$userId]);

        if (!$stmt->fetch()) {
            http_response_code(404);

            echo json_encode([
                "error" => "User not found"
            ]);

            exit();
        }

        $data = json_decode(
            file_get_contents("php://input"),
            true
        ) ?? [];

        $action = $data["action"] ?? "";


        // --------------------
        // Disable user
        // --------------------
        if ($action === "disable") {

            $stmt = $pdo->prepare(
                "UPDATE Users
                 SET IsEnabled = 0,
                     DateUpdated = NOW()
                 WHERE ID = ?"
            );

            $stmt->execute([$userId]);

            http_response_code(200);

            echo json_encode([
                "message" => "User disabled successfully"
            ]);

            exit();
        }


        // --------------------
        // Enable user
        // --------------------
        if ($action === "enable") {

            $stmt = $pdo->prepare(
                "UPDATE Users
                 SET IsEnabled = 1,
                     DateUpdated = NOW()
                 WHERE ID = ?"
            );

            $stmt->execute([$userId]);

            http_response_code(200);

            echo json_encode([
                "message" => "User enabled successfully"
            ]);

            exit();
        }


        // --------------------
        // Change password
        // --------------------
        if ($action === "changePassword") {

            $newPassword = $data["newPassword"] ?? "";

            if (empty($newPassword)) {
                http_response_code(400);

                echo json_encode([
                    "error" => "New password is required"
                ]);

                exit();
            }

            $passwordHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "UPDATE Users
                 SET Password = ?,
                     DateUpdated = NOW()
                 WHERE ID = ?"
            );

            $stmt->execute([
                $passwordHash,
                $userId
            ]);

            http_response_code(200);

            echo json_encode([
                "message" => "Password changed successfully"
            ]);

            exit();
        }


        http_response_code(400);

        echo json_encode([
            "error" => "Invalid action"
        ]);

        exit();
    }


    // ----------------------------------------------------
    // Unsupported HTTP method
    // ----------------------------------------------------
    http_response_code(405);

    echo json_encode([
        "error" => "Method not allowed"
    ]);


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "error" => "Database operation failed"
    ]);
}