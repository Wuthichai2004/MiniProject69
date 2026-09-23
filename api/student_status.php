<?php

require_once "auth.php";
require_once "db.php";

requireMethod("POST");

$session = requireAdmin();
requireCsrf();

$userId = filter_input(
    INPUT_POST,
    "user_id",
    FILTER_VALIDATE_INT
);

$status = trim(
    $_POST["status"] ?? ""
);

if (
    $userId === false ||
    $userId === null ||
    $userId < 1
) {
    jsonResponse(
        false,
        "รหัสนักศึกษาไม่ถูกต้อง",
        [],
        422
    );
}

if (
    $status !== "active" &&
    $status !== "suspended"
) {
    jsonResponse(
        false,
        "สถานะบัญชีไม่ถูกต้อง",
        [],
        422
    );
}

/* ตรวจว่าบัญชีนี้เป็น student จริง */
$check = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE user_id = ?
    AND role = 'student'
    LIMIT 1
");

$check->bind_param(
    "i",
    $userId
);

$check->execute();

if (
    $check->get_result()->num_rows !== 1
) {
    jsonResponse(
        false,
        "ไม่พบบัญชีนักศึกษา",
        [],
        404
    );
}

/* เปลี่ยนสถานะ */
$stmt = $conn->prepare("
    UPDATE users
    SET account_status = ?
    WHERE user_id = ?
    AND role = 'student'
");

$stmt->bind_param(
    "si",
    $status,
    $userId
);

if (!$stmt->execute()) {
    jsonResponse(
        false,
        "ไม่สามารถเปลี่ยนสถานะบัญชีได้",
        [],
        500
    );
}

$message =
    $status === "suspended"
        ? "ระงับบัญชีนักศึกษาเรียบร้อย"
        : "ปลดระงับบัญชีนักศึกษาเรียบร้อย";

jsonResponse(
    true,
    $message
);