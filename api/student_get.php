<?php

require_once "auth.php";
require_once "db.php";

requireAdmin();

$result = $conn->query("
    SELECT
        user_id,
        fullname,
        username,
        account_status
    FROM users
    WHERE role = 'student'
    ORDER BY user_id DESC
");

$data = [];

while ($row = $result->fetch_assoc()) {

    $data[] = [
        "user_id" => (int) $row["user_id"],
        "fullname" => $row["fullname"],
        "username" => $row["username"],
        "account_status" => $row["account_status"]
    ];
}

header("Content-Type: application/json; charset=UTF-8");

echo json_encode(
    $data,
    JSON_UNESCAPED_UNICODE
);