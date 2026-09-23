<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "waste_sorting");
if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => false, "message" => "ไม่สามารถเชื่อมต่อฐานข้อมูลได้"], JSON_UNESCAPED_UNICODE);
    exit;
}
$conn->set_charset("utf8mb4");
