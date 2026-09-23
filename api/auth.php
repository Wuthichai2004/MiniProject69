<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
        "path" => "/",
    ]);
    session_start();
}

function jsonResponse(bool $success, string $message, array $extra = [], int $status = 200): void
{
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(array_merge(["success" => $success, "message" => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function requireMethod(string $method): void
{
    if ($_SERVER["REQUEST_METHOD"] !== $method) {
        header("Allow: " . $method);
        jsonResponse(false, "วิธีการร้องขอไม่ถูกต้อง", [], 405);
    }
}

function requireLogin(): array
{
    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        jsonResponse(false, "กรุณาเข้าสู่ระบบก่อน", [], 401);
    }
    return $_SESSION;
}

function requireAdmin(): array
{
    $session = requireLogin();
    if ($session["role"] !== "admin") {
        jsonResponse(false, "คุณไม่มีสิทธิ์ดำเนินการนี้", [], 403);
    }
    return $session;
}

function csrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function requireCsrf(): void
{
    $provided = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
    if ($provided === "" || !hash_equals(csrfToken(), $provided)) {
        jsonResponse(false, "คำขอไม่ผ่านการตรวจสอบความปลอดภัย กรุณาเข้าสู่ระบบใหม่", [], 403);
    }
}
