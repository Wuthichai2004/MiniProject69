const API = "../api/";

document.addEventListener(
    "DOMContentLoaded",
    function () {
        loadReport();
    }
);

async function loadReport() {

    try {

        const response = await fetch(
            API + "report_get.php",
            {
                method: "GET",
                credentials: "same-origin"
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {

            throw new Error(
                result.message ||
                "ไม่สามารถโหลดรายงานได้"
            );

        }

        /*
        |--------------------------------------------------------------------------
        | รองรับรูปแบบ jsonResponse ของโปรเจกต์
        |--------------------------------------------------------------------------
        */

        const data =
            result.data ?? result;

        displaySummary(
            data.summary
        );

        displayWasteReport(
            data.waste_summary
        );

        displayStudentReport(
            data.students
        );

    } catch (error) {

        showMessage(
            error.message ||
            "เกิดข้อผิดพลาดในการโหลดข้อมูล"
        );

    }

}

/*
|--------------------------------------------------------------------------
| แสดงข้อมูลสรุป
|--------------------------------------------------------------------------
*/

function displaySummary(summary) {

    document.getElementById(
        "totalRecords"
    ).textContent =
        summary.total_records ?? 0;

    document.getElementById(
        "pendingRecords"
    ).textContent =
        summary.pending_records ?? 0;

    document.getElementById(
        "approvedRecords"
    ).textContent =
        summary.approved_records ?? 0;

    document.getElementById(
        "rejectedRecords"
    ).textContent =
        summary.rejected_records ?? 0;

    document.getElementById(
        "totalPoints"
    ).textContent =
        summary.total_points ?? 0;

}

/*
|--------------------------------------------------------------------------
| ตารางสรุปประเภทขยะ
|--------------------------------------------------------------------------
*/

function displayWasteReport(records) {

    const tbody =
        document.getElementById(
            "wasteReportBody"
        );

    tbody.innerHTML = "";

    if (
        !records ||
        records.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    ไม่มีข้อมูล
                </td>
            </tr>
        `;

        return;
    }

    records.forEach(
        function (record, index) {

            const row =
                document.createElement("tr");

            row.innerHTML = `
                <td>
                    ${index + 1}
                </td>

                <td>
                    ${escapeHtml(record.waste_name)}
                </td>

                <td>
                    ${Number(record.points).toLocaleString()} คะแนน
                </td>

                <td>
                    ${Number(record.total_records).toLocaleString()}
                </td>

                <td>
                    ${Number(record.total_quantity).toLocaleString()}
                </td>

                <td>
                    ${Number(record.total_points).toLocaleString()} คะแนน
                </td>
            `;

            tbody.appendChild(row);

        }
    );

}

/*
|--------------------------------------------------------------------------
| ตารางอันดับนักศึกษา
|--------------------------------------------------------------------------
*/

function displayStudentReport(students) {

    const tbody =
        document.getElementById(
            "studentReportBody"
        );

    tbody.innerHTML = "";

    if (
        !students ||
        students.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td colspan="4">
                    ไม่มีข้อมูลนักศึกษา
                </td>
            </tr>
        `;

        return;
    }

    students.forEach(
        function (student, index) {

            let rank = index + 1;

            if (index === 0) {
                rank = "🥇 1";
            }

            if (index === 1) {
                rank = "🥈 2";
            }

            if (index === 2) {
                rank = "🥉 3";
            }

            const row =
                document.createElement("tr");

            row.innerHTML = `
                <td>
                    ${rank}
                </td>

                <td>
                    ${escapeHtml(student.fullname)}
                </td>

                <td>
                    ${Number(student.total_records).toLocaleString()}
                </td>

                <td class="points">
                    ${Number(student.total_points).toLocaleString()}
                    คะแนน
                </td>
            `;

            tbody.appendChild(row);

        }
    );

}

/*
|--------------------------------------------------------------------------
| แสดง Error
|--------------------------------------------------------------------------
*/

function showMessage(text) {

    const message =
        document.getElementById(
            "message"
        );

    message.textContent = text;

    message.className =
        "message error";

}

/*
|--------------------------------------------------------------------------
| ป้องกัน HTML Injection
|--------------------------------------------------------------------------
*/

function escapeHtml(text) {

    if (
        text === null ||
        text === undefined
    ) {
        return "";
    }

    return String(text)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

}