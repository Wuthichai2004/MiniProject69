<?php

require_once "auth.php";
require_once "db.php";

requireMethod("POST");

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    jsonResponse(
        false,
        "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน",
        [],
        422
    );
}

$stmt = $conn->prepare(
    "SELECT
        user_id,
        fullname,
        username,
        password,
        role
     FROM users
     WHERE username = ?
     LIMIT 1"
);

if (!$stmt) {
    jsonResponse(
        false,
        "ไม่สามารถเตรียมคำสั่งเข้าสู่ระบบได้",
        [],
        500
    );
}

$stmt->bind_param("s", $username);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $stmt->close();

    jsonResponse(
        false,
        "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง",
        [],
        401
    );
}

$validPassword = password_verify(
    $password,
    $user["password"]
);

/*
|--------------------------------------------------------------------------
| รองรับรหัสผ่านแบบข้อความธรรมดาเดิม
|--------------------------------------------------------------------------
| หาก Login สำเร็จ ระบบจะเปลี่ยนเป็น password_hash ให้อัตโนมัติ
|--------------------------------------------------------------------------
*/

if (
    !$validPassword &&
    password_get_info($user["password"])["algo"] === null
) {
    $validPassword = hash_equals(
        (string) $user["password"],
        $password
    );

    if ($validPassword) {
        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $upgrade = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE user_id = ?"
        );

        if ($upgrade) {
            $userId = (int) $user["user_id"];

            $upgrade->bind_param(
                "si",
                $newHash,
                $userId
            );

            $upgrade->execute();
            $upgrade->close();
        }
    }
}

if (!$validPassword) {
    $stmt->close();

    jsonResponse(
        false,
        "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง",
        [],
        401
    );
}

/*
|--------------------------------------------------------------------------
| สร้าง Session หลัง Login สำเร็จ
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);

$_SESSION["user_id"] = (int) $user["user_id"];
$_SESSION["fullname"] = $user["fullname"];
$_SESSION["role"] = $user["role"];

/*
|--------------------------------------------------------------------------
| แยกหน้าปลายทางตาม role
|--------------------------------------------------------------------------
*/

if ($user["role"] === "admin") {
    $redirect = "../api/admin_dashboard.php";
} else {
    $redirect = "../main/record_waste.html";
}

$stmt->close();
$conn->close();

/*
|--------------------------------------------------------------------------
| ส่งข้อมูลกลับไปให้ login.js
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    "เข้าสู่ระบบสำเร็จ",
    [
        "csrf_token" => csrfToken(),
        "redirect" => $redirect,
        "user" => [
            "user_id" => (int) $user["user_id"],
            "fullname" => $user["fullname"],
            "username" => $user["username"],
            "role" => $user["role"]
        ]
    ]
);