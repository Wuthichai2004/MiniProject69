<?php
require_once "auth.php";
require_once "db.php";
requireLogin();

$result = $conn->query("SELECT waste_id, waste_name, points FROM waste_types ORDER BY waste_id DESC");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "waste_id" => (int) $row["waste_id"],
        "waste_name" => $row["waste_name"],
        "points" => (int) $row["points"],
    ];
}
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($data, JSON_UNESCAPED_UNICODE);
