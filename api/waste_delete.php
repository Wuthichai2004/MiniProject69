<?php
require_once "auth.php";
require_once "db.php";
requireMethod("POST");
requireAdmin();
requireCsrf();

$wasteId = filter_input(INPUT_POST, "waste_id", FILTER_VALIDATE_INT);
if ($wasteId === false || $wasteId < 1) { jsonResponse(false, "รหัสประเภทขยะไม่ถูกต้อง", [], 422); }

$stmt = $conn->prepare("DELETE FROM waste_types WHERE waste_id = ?");
$stmt->bind_param("i", $wasteId);
if (!$stmt->execute()) { jsonResponse(false, "ไม่สามารถลบข้อมูลได้ อาจมีรายการที่ใช้งานประเภทนี้อยู่", [], 409); }
jsonResponse(true, "ลบข้อมูลสำเร็จ");
