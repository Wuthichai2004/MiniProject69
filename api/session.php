<?php
require_once "auth.php";
$session = requireLogin();
jsonResponse(true, "เข้าสู่ระบบแล้ว", ["csrf_token" => csrfToken(), "user" => [
    "user_id" => (int) $session["user_id"],
    "fullname" => $session["fullname"] ?? "",
    "role" => $session["role"],
]]);
