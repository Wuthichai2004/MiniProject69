<?php
require_once "auth.php";
require_once "db.php";
requireMethod("POST");
requireAdmin();
requireCsrf();

$wasteName = trim($_POST["waste_name"] ?? "");
$points = filter_input(INPUT_POST, "points", FILTER_VALIDATE_INT);
if ($wasteName === "" || $points === false || $points < 1) {
    jsonResponse(false, "กรุณากรอกชื่อและคะแนนให้ถูกต้อง", [], 422);
}

$stmt = $conn->prepare("INSERT INTO waste_types (waste_name, points) VALUES (?, ?)");
$stmt->bind_param("si", $wasteName, $points);
if (!$stmt->execute()) { jsonResponse(false, "ไม่สามารถเพิ่มข้อมูลได้", [], 500); }
jsonResponse(true, "เพิ่มข้อมูลสำเร็จ");
