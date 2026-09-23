<?php

header("Content-Type: application/json");

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/auth.php";

$pdo = getDatabaseConnection();

$user = requireLogin($pdo);

$userId = $user["ID"];
$method = $_SERVER["REQUEST_METHOD"];

try {

    // ----------------------------------------------------
    // GET - Search/list contacts or get one contact
    // ----------------------------------------------------
    if ($method === "GET") {

        // GET /api/contacts.php?id=5
        if (isset($_GET["id"])) {

            $contactId = (int) $_GET["id"];

            $stmt = $pdo->prepare(
                "SELECT ID, FirstName, LastName, Phone, Email,
                        DateCreated, DateUpdated
                 FROM Contacts
                 WHERE ID = ?
                 AND UserID = ?"
            );

            $stmt->execute([
                $contactId,
                $userId
            ]);

            $contact = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contact) {
                http_response_code(404);

                echo json_encode([
                    "error" => "Contact not found"
                ]);

                exit();
            }

            http_response_code(200);

            echo json_encode([
                "contact" => $contact
            ]);

            exit();
        }


        // GET /api/contacts.php?q=john

        $search = trim($_GET["q"] ?? "");
        $searchTerm = "%" . $search . "%";

        $stmt = $pdo->prepare(
            "SELECT ID, FirstName, LastName, Phone, Email,
                    DateCreated, DateUpdated
             FROM Contacts
             WHERE UserID = ?
             AND (
                 FirstName LIKE ?
                 OR LastName LIKE ?
                 OR Email LIKE ?
                 OR Phone LIKE ?
             )
             ORDER BY LastName, FirstName
             LIMIT 50"
        );

        $stmt->execute([
            $userId,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm
        ]);

        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);

        echo json_encode([
            "contacts" => $contacts
        ]);

        exit();
    }


    // ----------------------------------------------------
    // POST - Create contact
    // ----------------------------------------------------
    if ($method === "POST") {

        $data = json_decode(
            file_get_contents("php://input"),
            true
        ) ?? [];

        $firstName = trim($data["firstName"] ?? "");
        $lastName  = trim($data["lastName"] ?? "");
        $email     = trim($data["email"] ?? "");
        $phone     = trim($data["phone"] ?? "");

        if (
            empty($firstName) ||
            empty($lastName)
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "First name and last name are required"
            ]);

            exit();
        }

        $stmt = $pdo->prepare(
            "INSERT INTO Contacts
             (FirstName, LastName, Phone, Email,
              DateCreated, DateUpdated, UserID)
             VALUES (?, ?, ?, ?, NOW(), NOW(), ?)"
        );

        $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $email,
            $userId
        ]);

        http_response_code(201);

        echo json_encode([
            "message" => "Contact created successfully",
            "id" => $pdo->lastInsertId()
        ]);

        exit();
    }


    // ----------------------------------------------------
    // PUT - Update contact
    // ----------------------------------------------------
    if ($method === "PUT") {

        if (!isset($_GET["id"])) {
            http_response_code(400);

            echo json_encode([
                "error" => "Contact ID is required"
            ]);

            exit();
        }

        $contactId = (int) $_GET["id"];

        $data = json_decode(
            file_get_contents("php://input"),
            true
        ) ?? [];

        $firstName = trim($data["firstName"] ?? "");
        $lastName  = trim($data["lastName"] ?? "");
        $email     = trim($data["email"] ?? "");
        $phone     = trim($data["phone"] ?? "");

        if (
            empty($firstName) ||
            empty($lastName)
        ) {
            http_response_code(400);

            echo json_encode([
                "error" => "First name and last name are required"
            ]);

            exit();
        }

        $stmt = $pdo->prepare(
            "UPDATE Contacts
             SET FirstName = ?,
                 LastName = ?,
                 Phone = ?,
                 Email = ?,
                 DateUpdated = NOW()
             WHERE ID = ?
             AND UserID = ?"
        );

        $stmt->execute([
            $firstName,
            $lastName,
            $phone,
            $email,
            $contactId,
            $userId
        ]);

        if ($stmt->rowCount() === 0) {

            // Could mean contact doesn't exist OR values didn't change.
            // Verify ownership/existence separately.

            $check = $pdo->prepare(
                "SELECT ID
                 FROM Contacts
                 WHERE ID = ?
                 AND UserID = ?"
            );

            $check->execute([
                $contactId,
                $userId
            ]);

            if (!$check->fetch()) {
                http_response_code(404);

                echo json_encode([
                    "error" => "Contact not found"
                ]);

                exit();
            }
        }

        http_response_code(200);

        echo json_encode([
            "message" => "Contact updated successfully"
        ]);

        exit();
    }


    // ----------------------------------------------------
    // DELETE - Delete contact
    // ----------------------------------------------------
    if ($method === "DELETE") {

        if (!isset($_GET["id"])) {
            http_response_code(400);

            echo json_encode([
                "error" => "Contact ID is required"
            ]);

            exit();
        }

        $contactId = (int) $_GET["id"];

        $stmt = $pdo->prepare(
            "DELETE FROM Contacts
             WHERE ID = ?
             AND UserID = ?"
        );

        $stmt->execute([
            $contactId,
            $userId
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);

            echo json_encode([
                "error" => "Contact not found"
            ]);

            exit();
        }

        http_response_code(200);

        echo json_encode([
            "message" => "Contact deleted successfully"
        ]);

        exit();
    }


    // ----------------------------------------------------
    // Any other HTTP method
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