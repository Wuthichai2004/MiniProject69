<?php

require_once "auth.php";
require_once "db.php";

requireMethod("POST");

$fullname = trim($_POST["fullname"] ?? "");
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";
$role = "student";


/* =========================
   ตรวจสอบชื่อ-นามสกุล
========================= */

if (
    $fullname === "" ||
    mb_strlen($fullname) > 100
) {
    jsonResponse(
        false,
        "กรุณากรอกชื่อให้ถูกต้อง",
        [],
        422
    );
}


/* =========================
   ตรวจสอบ Username
========================= */

if (
    !preg_match(
        '/^[A-Za-z0-9_.-]{4,50}$/',
        $username
    )
) {
    jsonResponse(
        false,
        "ชื่อผู้ใช้ต้องมี 4-50 ตัว และใช้ตัวอักษรอังกฤษ ตัวเลข _ . -",
        [],
        422
    );
}


/* =========================
   ตรวจสอบ Password
========================= */

if (
    strlen($password) < 8 ||
    strlen($password) > 72
) {
    jsonResponse(
        false,
        "รหัสผ่านต้องมี 8-72 ตัวอักษร",
        [],
        422
    );
}


/* =========================
   ตรวจสอบประเภทสมาชิก
========================= */

/* =========================
   ตรวจสอบ Username ซ้ำ
========================= */

$check = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE username = ?
     LIMIT 1"
);

if (!$check) {
    jsonResponse(
        false,
        "ไม่สามารถตรวจสอบชื่อผู้ใช้ได้",
        [],
        500
    );
}

$check->bind_param(
    "s",
    $username
);

$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    $check->close();

    jsonResponse(
        false,
        "ชื่อผู้ใช้นี้มีอยู่แล้ว",
        [],
        409
    );
}

$check->close();


/* =========================
   เข้ารหัส Password
========================= */

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($hashedPassword === false) {
    jsonResponse(
        false,
        "ไม่สามารถเข้ารหัสรหัสผ่านได้",
        [],
        500
    );
}


/* =========================
   บันทึกสมาชิก
========================= */

$stmt = $conn->prepare(
    "INSERT INTO users
    (
        fullname,
        username,
        password,
        role
    )
    VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    jsonResponse(
        false,
        "ไม่สามารถเตรียมคำสั่งสมัครสมาชิกได้",
        [],
        500
    );
}

$stmt->bind_param(
    "ssss",
    $fullname,
    $username,
    $hashedPassword,
    $role
);


/* =========================
   ตรวจสอบผลการบันทึก
========================= */

if (!$stmt->execute()) {

    $stmt->close();

    jsonResponse(
        false,
        "ไม่สามารถสมัครสมาชิกได้",
        [],
        500
    );
}

$userId = $stmt->insert_id;

$stmt->close();
$conn->close();


/* =========================
   ส่งผลลัพธ์กลับ
========================= */

$roleText = "นักศึกษา";

jsonResponse(
    true,
    "สมัครสมาชิกสำเร็จ ประเภทสมาชิก: " . $roleText,
    [
        "user_id" => $userId,
        "role" => $role
    ],
    201
);
