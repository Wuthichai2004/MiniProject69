<?php

require_once "auth.php";
require_once "db.php";

requireMethod("POST");

/*
|--------------------------------------------------------------------------
| เฉพาะ Admin
|--------------------------------------------------------------------------
*/

$session = requireAdmin();

$adminId = (int) $session["user_id"];


/*
|--------------------------------------------------------------------------
| รับข้อมูลจาก approve_waste.js
|--------------------------------------------------------------------------
*/

$recordId = filter_input(
    INPUT_POST,
    "record_id",
    FILTER_VALIDATE_INT
);

$status = trim(
    $_POST["status"] ?? ""
);


/*
|--------------------------------------------------------------------------
| ตรวจสอบข้อมูล
|--------------------------------------------------------------------------
*/

if (
    $recordId === false ||
    $recordId === null ||
    $recordId < 1
) {
    jsonResponse(
        false,
        "รหัสรายการไม่ถูกต้อง",
        [],
        422
    );
}

if (
    $status !== "approved" &&
    $status !== "rejected"
) {
    jsonResponse(
        false,
        "สถานะไม่ถูกต้อง",
        [],
        422
    );
}


/*
|--------------------------------------------------------------------------
| เริ่ม Transaction
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | ดึงข้อมูลจาก D3 + D2
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            wr.record_id,
            wr.user_id,
            wr.quantity,
            wr.status,
            wt.points

        FROM waste_records wr

        INNER JOIN waste_types wt
            ON wr.waste_id = wt.waste_id

        WHERE wr.record_id = ?

        FOR UPDATE
    ");

    if (!$stmt) {
        throw new Exception(
            "ไม่สามารถเตรียมคำสั่งตรวจสอบรายการได้"
        );
    }

    $stmt->bind_param(
        "i",
        $recordId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $record = $result->fetch_assoc();


    if (!$record) {
        throw new Exception(
            "ไม่พบรายการแยกขยะ"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ต้องเป็น pending เท่านั้น
    |--------------------------------------------------------------------------
    */

    if ($record["status"] !== "pending") {
        throw new Exception(
            "รายการนี้ได้รับการตรวจสอบแล้ว"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | คำนวณคะแนน
    |--------------------------------------------------------------------------
    */

    $pointsEarned = 0;

    if ($status === "approved") {

        $pointsEarned =
            (int) $record["quantity"]
            *
            (int) $record["points"];
    }


    /*
    |--------------------------------------------------------------------------
    | 1. Update D3 : waste_records
    |--------------------------------------------------------------------------
    */

    $updateRecord = $conn->prepare("
        UPDATE waste_records

        SET
            status = ?,
            points_earned = ?,
            reviewed_at = NOW()

        WHERE record_id = ?
    ");

    if (!$updateRecord) {
        throw new Exception(
            "ไม่สามารถเตรียมคำสั่งอัปเดตรายการได้"
        );
    }

    $updateRecord->bind_param(
        "sii",
        $status,
        $pointsEarned,
        $recordId
    );

    if (!$updateRecord->execute()) {
        throw new Exception(
            "ไม่สามารถอัปเดตรายการแยกขยะได้"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 2. INSERT D5 : waste_approvals
    |--------------------------------------------------------------------------
    |
    | ฐานข้อมูลจริงใช้:
    | points_awarded
    | approved_at
    |--------------------------------------------------------------------------
    */

    $remark =
        $status === "approved"
            ? "อนุมัติรายการ"
            : "ไม่อนุมัติรายการ";


    $approval = $conn->prepare("
        INSERT INTO waste_approvals
        (
            record_id,
            admin_id,
            status,
            points_awarded,
            remark
        )

        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$approval) {
        throw new Exception(
            "ไม่สามารถเตรียมคำสั่งบันทึกการอนุมัติได้"
        );
    }

    $approval->bind_param(
        "iisis",
        $recordId,
        $adminId,
        $status,
        $pointsEarned,
        $remark
    );

    if (!$approval->execute()) {
        throw new Exception(
            "ไม่สามารถบันทึกข้อมูลการอนุมัติได้"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 3. ถ้าอนุมัติ Update D4 : user_points
    |--------------------------------------------------------------------------
    |
    | D4 จริงเป็นคะแนนรวมของแต่ละ user
    |
    | user_id
    | total_points
    | updated_at
    |--------------------------------------------------------------------------
    */

    if ($status === "approved") {

        $userId =
            (int) $record["user_id"];


        /*
        |--------------------------------------------------------------------------
        | ถ้ายังไม่มี user_id → INSERT
        | ถ้ามีแล้ว → บวก total_points เพิ่ม
        |--------------------------------------------------------------------------
        */

        $pointStmt = $conn->prepare("
            INSERT INTO user_points
            (
                user_id,
                total_points
            )

            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE

                total_points =
                    total_points + VALUES(total_points),

                updated_at =
                    CURRENT_TIMESTAMP
        ");

        if (!$pointStmt) {
            throw new Exception(
                "ไม่สามารถเตรียมคำสั่งบันทึกคะแนนสะสมได้"
            );
        }

        $pointStmt->bind_param(
            "ii",
            $userId,
            $pointsEarned
        );

        if (!$pointStmt->execute()) {
            throw new Exception(
                "ไม่สามารถบันทึกคะแนนสะสมได้"
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ยืนยัน Transaction
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | ตอบกลับ
    |--------------------------------------------------------------------------
    */

    if ($status === "approved") {

        jsonResponse(
            true,
            "อนุมัติรายการสำเร็จ ได้รับ {$pointsEarned} คะแนน",
            [
                "record_id" => $recordId,
                "status" => "approved",
                "points_earned" => $pointsEarned
            ]
        );

    } else {

        jsonResponse(
            true,
            "ไม่อนุมัติรายการสำเร็จ",
            [
                "record_id" => $recordId,
                "status" => "rejected",
                "points_earned" => 0
            ]
        );
    }


} catch (Throwable $e) {

    $conn->rollback();

    jsonResponse(
        false,
        $e->getMessage(),
        [],
        500
    );
}