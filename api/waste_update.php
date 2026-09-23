<?php
require_once "auth.php";
require_once "db.php";
requireMethod("POST");
requireAdmin();
requireCsrf();

$wasteId = filter_input(INPUT_POST, "waste_id", FILTER_VALIDATE_INT);
$wasteName = trim($_POST["waste_name"] ?? "");
$points = filter_input(INPUT_POST, "points", FILTER_VALIDATE_INT);
if ($wasteId === false || $wasteId < 1 || $wasteName === "" || $points === false || $points < 1) {
    jsonResponse(false, "ข้อมูลที่ส่งมาไม่ถูกต้อง", [], 422);
}

$stmt = $conn->prepare("UPDATE waste_types SET waste_name = ?, points = ? WHERE waste_id = ?");
$stmt->bind_param("sii", $wasteName, $points, $wasteId);
if (!$stmt->execute()) { jsonResponse(false, "ไม่สามารถแก้ไขข้อมูลได้", [], 500); }
jsonResponse(true, "แก้ไขข้อมูลสำเร็จ");
