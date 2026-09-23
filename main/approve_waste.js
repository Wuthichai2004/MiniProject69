document.addEventListener("DOMContentLoaded", async function () {
    try {
        await refreshSession();
        await loadPendingRecords();
    } catch (error) {
        alert(error.message || "กรุณาเข้าสู่ระบบใหม่");

        localStorage.removeItem("user");
        sessionStorage.clear();

        window.location.href = "login.html";
    }
});


/* =========================================
   ตรวจสอบ Session และรับ CSRF Token
========================================= */
async function refreshSession() {
    const response = await fetch("../api/session.php", {
        method: "GET",
        credentials: "same-origin",
        cache: "no-store"
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(
            result.message || "Session หมดอายุ กรุณาเข้าสู่ระบบใหม่"
        );
    }

    if (!result.user || result.user.role !== "admin") {
        throw new Error(
            "หน้านี้สำหรับเจ้าหน้าที่เท่านั้น"
        );
    }

    if (result.csrf_token) {
        sessionStorage.setItem(
            "csrf_token",
            result.csrf_token
        );
    }

    return result.csrf_token || "";
}


/* =========================================
   โหลดรายการรออนุมัติ
========================================= */
async function loadPendingRecords() {
    const recordBody =
        document.getElementById("recordBody");

    recordBody.innerHTML = `
        <tr>
            <td colspan="9">
                กำลังโหลดข้อมูล...
            </td>
        </tr>
    `;

    try {
        const response = await fetch(
            "../api/waste_pending_get.php",
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "โหลดข้อมูลไม่สำเร็จ"
            );
        }

        showRecords(result.data);

    } catch (error) {

        recordBody.innerHTML = `
            <tr>
                <td colspan="9">
                    ${escapeHtml(error.message)}
                </td>
            </tr>
        `;
    }
}


/* =========================================
   แสดงรายการในตาราง
========================================= */
function showRecords(records) {
    const recordBody =
        document.getElementById("recordBody");

    recordBody.innerHTML = "";

    if (!records || records.length === 0) {
        recordBody.innerHTML = `
            <tr>
                <td colspan="9">
                    ไม่มีรายการรออนุมัติ
                </td>
            </tr>
        `;
        return;
    }

    records.forEach(function (record) {

        const row = document.createElement("tr");

        const recordId = parseInt(
            record.record_id,
            10
        );

        const imageHtml = record.image
            ? `
                <a
                    class="evidence-link"
                    href="../uploads/${encodeURIComponent(record.image)}"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="เปิดดูรูปขนาดเต็ม"
                >
                    <img
                        class="evidence-image"
                        src="../uploads/${encodeURIComponent(record.image)}"
                        alt="รูปหลักฐานรายการ ${recordId}"
                        loading="lazy"
                    >
                    <span>ดูรูป</span>
                </a>
            `
            : `<span class="no-evidence">ไม่มีรูป</span>`;

        row.innerHTML = `
            <td>${recordId}</td>

            <td>
                ${escapeHtml(record.fullname)}
            </td>

            <td>
                ${escapeHtml(record.waste_name)}
            </td>

            <td>
                ${record.quantity}
            </td>

            <td>
                ${record.points}
            </td>

            <td>
                ${record.total_points}
            </td>

            <td>
                ${imageHtml}
            </td>

            <td>
                ${escapeHtml(record.created_at)}
            </td>

            <td>
                <button
                    type="button"
                    class="approve-btn"
                    data-id="${recordId}"
                    data-action="approve"
                >
                    อนุมัติ
                </button>

                <button
                    type="button"
                    class="reject-btn"
                    data-id="${recordId}"
                    data-action="reject"
                >
                    ไม่อนุมัติ
                </button>
            </td>
        `;

        recordBody.appendChild(row);
    });


    /* =====================================
       ผูก Event ให้ปุ่ม
    ===================================== */
    document
        .querySelectorAll(".approve-btn, .reject-btn")
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const recordId =
                        parseInt(
                            this.dataset.id,
                            10
                        );

                    const action =
                        this.dataset.action;

                    reviewRecord(
                        recordId,
                        action
                    );
                }
            );
        });
}


/* =========================================
   อนุมัติ / ไม่อนุมัติ
========================================= */
async function reviewRecord(recordId, action) {

    recordId = parseInt(recordId, 10);

    /* ตรวจสอบ record_id */
    if (
        Number.isNaN(recordId) ||
        recordId < 1
    ) {
        showMessage(
            "รหัสรายการไม่ถูกต้อง",
            "error"
        );

        return;
    }


    /* ตรวจสอบ action */
    if (
        action !== "approve" &&
        action !== "reject"
    ) {
        showMessage(
            "คำสั่งไม่ถูกต้อง",
            "error"
        );

        return;
    }


    const actionText =
        action === "approve"
            ? "อนุมัติ"
            : "ไม่อนุมัติ";


    const confirmed = confirm(
        `คุณต้องการ${actionText}รายการรหัส ${recordId} หรือไม่?`
    );

    if (!confirmed) {
        return;
    }


    try {

        /* =====================================
           รับ CSRF Token ล่าสุด
        ===================================== */
        const csrfToken =
            await refreshSession();


        /* =====================================
           แปลง action ให้ตรงกับฐานข้อมูล

           approve -> approved
           reject  -> rejected
        ===================================== */
        const status =
            action === "approve"
                ? "approved"
                : "rejected";


        /* =====================================
           สร้าง POST FormData

           PHP จะได้รับ:
           $_POST["record_id"]
           $_POST["status"]
        ===================================== */
        const formData =
            new FormData();

        formData.append(
            "record_id",
            String(recordId)
        );

        formData.append(
            "status",
            status
        );


        /* =====================================
           ส่งไป waste_review.php
        ===================================== */
        const response = await fetch(
            "../api/waste_review.php",
            {
                method: "POST",

                credentials: "same-origin",

                cache: "no-store",

                headers: {
                    "X-CSRF-Token": csrfToken
                },

                body: formData
            }
        );


        /* =====================================
           อ่าน Response
        ===================================== */
        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {
            throw new Error(
                result.message ||
                "ดำเนินการไม่สำเร็จ"
            );
        }


        /* =====================================
           สำเร็จ
        ===================================== */
        showMessage(
            result.message,
            "success"
        );


        /* โหลดรายการ pending ใหม่ */
        await loadPendingRecords();

    } catch (error) {

        console.error(error);

        showMessage(
            error.message ||
            "เกิดข้อผิดพลาด",
            "error"
        );
    }
}


/* =========================================
   แสดงข้อความ
========================================= */
function showMessage(text, type) {

    const message =
        document.getElementById("message");

    if (!message) {
        alert(text);
        return;
    }

    message.textContent = text;

    message.className =
        `message ${type}`;


    setTimeout(function () {

        message.textContent = "";

        message.className =
            "message";

    }, 4000);
}


/* =========================================
   ป้องกัน HTML Injection
========================================= */
function escapeHtml(text) {

    return String(text ?? "")

        .replaceAll(
            "&",
            "&amp;"
        )

        .replaceAll(
            "<",
            "&lt;"
        )

        .replaceAll(
            ">",
            "&gt;"
        )

        .replaceAll(
            '"',
            "&quot;"
        )

        .replaceAll(
            "'",
            "&#039;"
        );
}
