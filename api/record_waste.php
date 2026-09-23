<?php

require_once "auth.php";
require_once "db.php";

requireMethod("POST");

$session = requireLogin();
requireCsrf();

/*
|--------------------------------------------------------------------------
| ข้อมูลผู้ใช้งานที่เข้าสู่ระบบ
|--------------------------------------------------------------------------
*/

$userId = (int) $session["user_id"];

/*
|--------------------------------------------------------------------------
| อนุญาตเฉพาะนักศึกษา
|--------------------------------------------------------------------------
*/

if (
    !isset($session["role"]) ||
    $session["role"] !== "student"
) {
    jsonResponse(
        false,
        "หน้านี้อนุญาตเฉพาะนักศึกษา",
        [],
        403
    );
}

/*
|--------------------------------------------------------------------------
| รับข้อมูลจากแบบฟอร์ม
|--------------------------------------------------------------------------
*/

$wasteId = filter_input(
    INPUT_POST,
    "waste_id",
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    "quantity",
    FILTER_VALIDATE_INT
);

/*
|--------------------------------------------------------------------------
| ตรวจสอบประเภทขยะและจำนวน
|--------------------------------------------------------------------------
*/

if (
    $wasteId === false ||
    $wasteId === null ||
    $wasteId < 1 ||
    $quantity === false ||
    $quantity === null ||
    $quantity < 1
) {
    jsonResponse(
        false,
        "กรุณากรอกประเภทและจำนวนให้ถูกต้อง",
        [],
        422
    );
}

/*
|--------------------------------------------------------------------------
| ตรวจสอบว่าประเภทขยะมีอยู่จริง
|--------------------------------------------------------------------------
*/

$wasteCheck = $conn->prepare(
    "SELECT waste_id
     FROM waste_types
     WHERE waste_id = ?
     LIMIT 1"
);

if (!$wasteCheck) {
    jsonResponse(
        false,
        "ไม่สามารถตรวจสอบประเภทขยะได้",
        [],
        500
    );
}

$wasteCheck->bind_param("i", $wasteId);
$wasteCheck->execute();

$wasteResult = $wasteCheck->get_result();

if ($wasteResult->num_rows !== 1) {
    $wasteCheck->close();

    jsonResponse(
        false,
        "ไม่พบประเภทขยะที่เลือก",
        [],
        422
    );
}

$wasteCheck->close();

/*
|--------------------------------------------------------------------------
| อัปโหลดรูปภาพหลักฐาน
|--------------------------------------------------------------------------
*/

$imageName = null;

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {
    $image = $_FILES["image"];

    /*
    |----------------------------------------------------------------------
    | ตรวจสอบข้อผิดพลาดและขนาดไฟล์
    |----------------------------------------------------------------------
    */

    if ($image["error"] !== UPLOAD_ERR_OK) {
        jsonResponse(
            false,
            "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ",
            [],
            422
        );
    }

    if ($image["size"] > 5 * 1024 * 1024) {
        jsonResponse(
            false,
            "รูปภาพต้องมีขนาดไม่เกิน 5 MB",
            [],
            422
        );
    }

    /*
    |----------------------------------------------------------------------
    | ประเภทไฟล์ที่อนุญาต
    |----------------------------------------------------------------------
    */

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $fileInfo->file($image["tmp_name"]);

    if (!isset($allowedTypes[$mime])) {
        jsonResponse(
            false,
            "รองรับเฉพาะรูป JPG, PNG และ WebP",
            [],
            422
        );
    }

    /*
    |----------------------------------------------------------------------
    | สร้างโฟลเดอร์ uploads
    |----------------------------------------------------------------------
    */

    $uploadDir =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        "uploads";

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            jsonResponse(
                false,
                "ไม่สามารถสร้างโฟลเดอร์เก็บรูปได้",
                [],
                500
            );
        }
    }

    /*
    |----------------------------------------------------------------------
    | สร้างชื่อไฟล์ใหม่
    |----------------------------------------------------------------------
    */

    try {
        $randomName = bin2hex(random_bytes(16));
    } catch (Exception $error) {
        jsonResponse(
            false,
            "ไม่สามารถสร้างชื่อไฟล์ได้",
            [],
            500
        );
    }

    $imageName =
        $randomName .
        "." .
        $allowedTypes[$mime];

    $imagePath =
        $uploadDir .
        DIRECTORY_SEPARATOR .
        $imageName;

    /*
    |----------------------------------------------------------------------
    | ย้ายรูปไปยังโฟลเดอร์ uploads
    |----------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $image["tmp_name"],
            $imagePath
        )
    ) {
        jsonResponse(
            false,
            "ไม่สามารถบันทึกรูปภาพได้",
            [],
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| บันทึกรายการแยกขยะ
|--------------------------------------------------------------------------
|
| นักศึกษาจะไม่ได้คะแนนทันที
|
| status         = pending
| points_earned  = 0
|
| ต้องรอเจ้าหน้าที่ Admin อนุมัติใน Process 5
|--------------------------------------------------------------------------
*/

$sql = "
    INSERT INTO waste_records
    (
        user_id,
        waste_id,
        quantity,
        image,
        status,
        points_earned,
        created_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        'pending',
        0,
        NOW()
    )
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    /*
    | ลบรูปออก หากเตรียมคำสั่ง SQL ไม่สำเร็จ
    */

    if ($imageName !== null) {
        $uploadedFile =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "uploads" .
            DIRECTORY_SEPARATOR .
            $imageName;

        if (file_exists($uploadedFile)) {
            @unlink($uploadedFile);
        }
    }

    jsonResponse(
        false,
        "ไม่สามารถเตรียมคำสั่งบันทึกข้อมูลได้",
        [],
        500
    );
}

$stmt->bind_param(
    "iiis",
    $userId,
    $wasteId,
    $quantity,
    $imageName
);

/*
|--------------------------------------------------------------------------
| ตรวจสอบผลการบันทึก
|--------------------------------------------------------------------------
*/

if (!$stmt->execute()) {
    /*
    | ลบรูปออก หากบันทึกฐานข้อมูลไม่สำเร็จ
    */

    if ($imageName !== null) {
        $uploadedFile =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            "uploads" .
            DIRECTORY_SEPARATOR .
            $imageName;

        if (file_exists($uploadedFile)) {
            @unlink($uploadedFile);
        }
    }

    $stmt->close();

    jsonResponse(
        false,
        "ไม่สามารถบันทึกข้อมูลได้",
        [],
        500
    );
}

$recordId = $stmt->insert_id;

$stmt->close();
$conn->close();

/*
|--------------------------------------------------------------------------
| ส่งผลลัพธ์กลับไปยัง JavaScript
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    "บันทึกการแยกขยะสำเร็จ รอเจ้าหน้าที่ตรวจสอบและอนุมัติคะแนน",
    [
        "record_id" => $recordId,
        "status" => "pending",
        "points_earned" => 0
    ],
    201
);