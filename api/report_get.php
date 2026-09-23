<?php

require_once "auth.php";
require_once "db.php";

requireMethod("GET");

$session = requireLogin();

if (
    !isset($session["role"]) ||
    $session["role"] !== "admin"
) {
    jsonResponse(
        false,
        "อนุญาตเฉพาะเจ้าหน้าที่",
        [],
        403
    );
}

/*
|--------------------------------------------------------------------------
| ข้อมูลสรุปภาพรวม
|--------------------------------------------------------------------------
*/

$sqlSummary = "
    SELECT
        COUNT(*) AS total_records,

        SUM(
            CASE
                WHEN status = 'pending' THEN 1
                ELSE 0
            END
        ) AS pending_records,

        SUM(
            CASE
                WHEN status = 'approved' THEN 1
                ELSE 0
            END
        ) AS approved_records,

        SUM(
            CASE
                WHEN status = 'rejected' THEN 1
                ELSE 0
            END
        ) AS rejected_records,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'approved'
                    THEN points_earned
                    ELSE 0
                END
            ),
            0
        ) AS total_points

    FROM waste_records
";

$summaryResult = $conn->query($sqlSummary);

if (!$summaryResult) {
    jsonResponse(
        false,
        "ไม่สามารถโหลดข้อมูลสรุปได้",
        [],
        500
    );
}

$summary = $summaryResult->fetch_assoc();

/*
|--------------------------------------------------------------------------
| สรุปข้อมูลตามประเภทขยะ
|--------------------------------------------------------------------------
*/

$sqlWaste = "
    SELECT
        wt.waste_id,
        wt.waste_name,
        wt.points,

        COUNT(wr.record_id) AS total_records,

        COALESCE(
            SUM(wr.quantity),
            0
        ) AS total_quantity,

        COALESCE(
            SUM(
                CASE
                    WHEN wr.status = 'approved'
                    THEN wr.points_earned
                    ELSE 0
                END
            ),
            0
        ) AS total_points

    FROM waste_types wt

    LEFT JOIN waste_records wr
        ON wt.waste_id = wr.waste_id

    GROUP BY
        wt.waste_id,
        wt.waste_name,
        wt.points

    ORDER BY total_quantity DESC
";

$wasteResult = $conn->query($sqlWaste);

if (!$wasteResult) {
    jsonResponse(
        false,
        "ไม่สามารถโหลดข้อมูลประเภทขยะได้",
        [],
        500
    );
}

$wasteSummary = [];

while ($row = $wasteResult->fetch_assoc()) {
    $wasteSummary[] = $row;
}

/*
|--------------------------------------------------------------------------
| นักศึกษาที่มีคะแนนสูงสุด
|--------------------------------------------------------------------------
*/

$sqlStudents = "
    SELECT
        u.user_id,
        u.fullname,

        COUNT(wr.record_id) AS total_records,

        COALESCE(
            SUM(
                CASE
                    WHEN wr.status = 'approved'
                    THEN wr.points_earned
                    ELSE 0
                END
            ),
            0
        ) AS total_points

    FROM users u

    LEFT JOIN waste_records wr
        ON u.user_id = wr.user_id

    WHERE u.role = 'student'

    GROUP BY
        u.user_id,
        u.fullname

    ORDER BY total_points DESC

    LIMIT 10
";

$studentResult = $conn->query($sqlStudents);

if (!$studentResult) {
    jsonResponse(
        false,
        "ไม่สามารถโหลดข้อมูลนักศึกษาได้",
        [],
        500
    );
}

$students = [];

while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row;
}

$conn->close();

jsonResponse(
    true,
    "โหลดรายงานสำเร็จ",
    [
        "summary" => $summary,
        "waste_summary" => $wasteSummary,
        "students" => $students
    ]
);