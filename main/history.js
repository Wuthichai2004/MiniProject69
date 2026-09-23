const API_URL = "../api/history_get.php";

document.addEventListener("DOMContentLoaded", function () {
    loadHistory();
});

async function loadHistory() {
    const tableBody = document.getElementById("historyTableBody");
    const totalPoints = document.getElementById("totalPoints");
    const fullname = document.getElementById("fullname");

    tableBody.innerHTML = `
        <tr>
            <td colspan="8">กำลังโหลดข้อมูล...</td>
        </tr>
    `;

    try {
        const response = await fetch(API_URL, {
            method: "GET",
            credentials: "same-origin"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "ไม่สามารถโหลดข้อมูลได้"
            );
        }

        fullname.textContent = result.fullname;
        totalPoints.textContent = result.total_points;

        displayHistory(result.data);
    } catch (error) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8">
                    ${escapeHtml(error.message)}
                </td>
            </tr>
        `;

        showMessage(error.message, "error");
    }
}

function displayHistory(records) {
    const tableBody = document.getElementById("historyTableBody");

    tableBody.innerHTML = "";

    if (!records || records.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8">
                    ยังไม่มีประวัติการแยกขยะ
                </td>
            </tr>
        `;

        return;
    }

    records.forEach(function (record) {
        const row = document.createElement("tr");

        let imageHtml = "ไม่มีรูป";

        if (record.image) {
            imageHtml = `
                <img
                    src="../uploads/${encodeURIComponent(record.image)}"
                    alt="รูปขยะ"
                    class="waste-image"
                >
            `;
        }

        const statusData = getStatus(record.status);

        row.innerHTML = `
            <td>${record.record_id}</td>

            <td>${escapeHtml(record.waste_name)}</td>

            <td>${record.quantity}</td>

            <td>${record.points_per_unit}</td>

            <td>${record.points_earned}</td>

            <td>${imageHtml}</td>

            <td>
                <span class="status ${statusData.className}">
                    ${statusData.text}
                </span>
            </td>

            <td>${formatDate(record.created_at)}</td>
        `;

        tableBody.appendChild(row);
    });
}

function getStatus(status) {
    if (status === "approved") {
        return {
            text: "อนุมัติแล้ว",
            className: "approved"
        };
    }

    if (status === "rejected") {
        return {
            text: "ไม่อนุมัติ",
            className: "rejected"
        };
    }

    return {
        text: "รอตรวจสอบ",
        className: "pending"
    };
}

function formatDate(dateText) {
    if (!dateText) {
        return "-";
    }

    const date = new Date(dateText.replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return dateText;
    }

    return date.toLocaleString("th-TH");
}

function showMessage(text, type) {
    const message = document.getElementById("message");

    message.textContent = text;
    message.className = `message ${type}`;
}

function escapeHtml(text) {
    if (text === null || text === undefined) {
        return "";
    }

    return String(text)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}