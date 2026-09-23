<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/auth.php";

$pdo = getDatabaseConnection();
$admin = requireAdmin($pdo);

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "error" => "Method not allowed"
    ]);

    exit();
}

try {

    $search = trim($_GET["q"] ?? "");
    $searchTerm = "%" . $search . "%";

    $stmt = $pdo->prepare(
        "SELECT
            Contacts.ID,
            Contacts.FirstName,
            Contacts.LastName,
            Contacts.Phone,
            Contacts.Email,
            Contacts.DateCreated,
            Contacts.DateUpdated,
            Contacts.UserID,
            Users.Login AS OwnerLogin,
            Users.FirstName AS OwnerFirstName,
            Users.LastName AS OwnerLastName
         FROM Contacts

         INNER JOIN Users
         ON Contacts.UserID = Users.ID

         WHERE Contacts.FirstName LIKE ?
            OR Contacts.LastName LIKE ?
            OR Contacts.Phone LIKE ?
            OR Contacts.Email LIKE ?
            OR Users.Login LIKE ?

         ORDER BY Contacts.LastName,
                  Contacts.FirstName

         LIMIT 50"
    );

    $stmt->execute([
        $searchTerm,
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


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "error" => "Database operation failed"
    ]);
}