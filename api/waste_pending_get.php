<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "อนุญาตเฉพาะเจ้าหน้าที่"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$sql = "SELECT
            wr.record_id,
            wr.quantity,
            wr.image,
            wr.status,
            wr.created_at,
            u.fullname,
            wt.waste_name,
            wt.points,
            (wr.quantity * wt.points) AS total_points
        FROM waste_records wr
        INNER JOIN users u
            ON wr.user_id = u.user_id
        INNER JOIN waste_types wt
            ON wr.waste_id = wt.waste_id
        WHERE wr.status = 'pending'
        ORDER BY wr.created_at DESC";

$result = $conn->query($sql);

$records = [];

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $records
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>