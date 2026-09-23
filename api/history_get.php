<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือยัง
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "กรุณาเข้าสู่ระบบก่อน"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

// อนุญาตเฉพาะนักศึกษา
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "student") {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "ไม่มีสิทธิ์เข้าใช้งาน"
    ], JSON_UNESCAPED_UNICODE);

    exit();
}

$userId = (int) $_SESSION["user_id"];

// ดึงคะแนนรวม เฉพาะรายการที่อนุมัติ
$sqlTotal = "SELECT
                COALESCE(SUM(points_earned), 0) AS total_points
             FROM waste_records
             WHERE user_id = ?
             AND status = 'approved'";

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->bind_param("i", $userId);
$stmtTotal->execute();

$totalResult = $stmtTotal->get_result();
$totalRow = $totalResult->fetch_assoc();

$totalPoints = (int) $totalRow["total_points"];

// ดึงประวัติการแยกขยะ
$sqlHistory = "SELECT
                    wr.record_id,
                    wt.waste_name,
                    wt.points AS points_per_unit,
                    wr.quantity,
                    wr.points_earned,
                    wr.image,
                    wr.status,
                    wr.created_at,
                    wr.reviewed_at
               FROM waste_records wr
               INNER JOIN waste_types wt
                   ON wr.waste_id = wt.waste_id
               WHERE wr.user_id = ?
               ORDER BY wr.created_at DESC";

$stmtHistory = $conn->prepare($sqlHistory);
$stmtHistory->bind_param("i", $userId);
$stmtHistory->execute();

$result = $stmtHistory->get_result();

$history = [];

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    "success" => true,
    "fullname" => $_SESSION["fullname"] ?? "นักศึกษา",
    "total_points" => $totalPoints,
    "data" => $history
], JSON_UNESCAPED_UNICODE);

$stmtTotal->close();
$stmtHistory->close();
$conn->close();
?>