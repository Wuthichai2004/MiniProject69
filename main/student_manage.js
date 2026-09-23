const API = "../api/";

document.addEventListener(
    "DOMContentLoaded",
    function () {
        loadStudents();
    }
);


/* =========================================
   โหลดรายชื่อนักศึกษา
========================================= */
async function loadStudents() {

    const studentTable =
        document.getElementById("studentTable");

    studentTable.innerHTML = `
        <tr>
            <td colspan="5">
                กำลังโหลดข้อมูล...
            </td>
        </tr>
    `;

    try {

        const response = await fetch(
            API + "student_get.php",
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ||
                "โหลดข้อมูลนักศึกษาไม่สำเร็จ"
            );
        }

        studentTable.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {

            studentTable.innerHTML = `
                <tr>
                    <td colspan="5">
                        ยังไม่มีบัญชีนักศึกษา
                    </td>
                </tr>
            `;

            return;
        }

        data.forEach(
            function (student, index) {

                const row =
                    document.createElement("tr");

                const isSuspended =
                    student.account_status === "suspended";

                const statusText =
                    isSuspended
                        ? "ถูกระงับ"
                        : "ใช้งานปกติ";

                const buttonText =
                    isSuspended
                        ? "ปลดระงับ"
                        : "ระงับบัญชี";

                const newStatus =
                    isSuspended
                        ? "active"
                        : "suspended";

                row.innerHTML = `
                    <td>${index + 1}</td>

                    <td>
                        ${escapeHtml(student.fullname)}
                    </td>

                    <td>
                        ${escapeHtml(student.username)}
                    </td>

                    <td>
                        ${statusText}
                    </td>

                    <td>
                        <button
                            type="button"
                            class="${
                                isSuspended
                                    ? "active-btn"
                                    : "suspend-btn"
                            }"
                        >
                            ${buttonText}
                        </button>
                    </td>
                `;

                const button =
                    row.querySelector("button");

                button.addEventListener(
                    "click",
                    function () {
                        changeStudentStatus(
                            student.user_id,
                            student.fullname,
                            newStatus
                        );
                    }
                );

                studentTable.appendChild(row);
            }
        );

    } catch (error) {

        studentTable.innerHTML = `
            <tr>
                <td colspan="5">
                    ${escapeHtml(error.message)}
                </td>
            </tr>
        `;
    }
}


/* =========================================
   ระงับ / ปลดระงับ
========================================= */
async function changeStudentStatus(
    userId,
    fullname,
    newStatus
) {

    const actionText =
        newStatus === "suspended"
            ? "ระงับ"
            : "ปลดระงับ";

    if (
        !confirm(
            `คุณต้องการ${actionText}บัญชี "${fullname}" หรือไม่?`
        )
    ) {
        return;
    }

    try {

        const sessionResponse =
            await fetch(
                API + "session.php",
                {
                    method: "GET",
                    credentials: "same-origin",
                    cache: "no-store"
                }
            );

        const sessionData =
            await sessionResponse.json();

        if (
            !sessionResponse.ok ||
            !sessionData.success
        ) {
            throw new Error(
                "กรุณาเข้าสู่ระบบใหม่"
            );
        }

        const csrfToken =
            sessionData.csrf_token;

        const formData =
            new FormData();

        formData.append(
            "user_id",
            userId
        );

        formData.append(
            "status",
            newStatus
        );

        const response =
            await fetch(
                API + "student_status.php",
                {
                    method: "POST",

                    credentials:
                        "same-origin",

                    headers: {
                        "X-CSRF-Token":
                            csrfToken
                    },

                    body: formData
                }
            );

        const data =
            await response.json();

        if (
            !response.ok ||
            !data.success
        ) {
            throw new Error(
                data.message ||
                "เปลี่ยนสถานะบัญชีไม่สำเร็จ"
            );
        }

        alert(data.message);

        await loadStudents();

    } catch (error) {

        alert(
            error.message ||
            "เกิดข้อผิดพลาด"
        );
    }
}


/* =========================================
   ป้องกัน HTML Injection
========================================= */
function escapeHtml(text) {

    return String(text ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}