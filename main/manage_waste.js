const API = "../api/";


/* =========================================
   เริ่มทำงานเมื่อเปิดหน้า
========================================= */
document.addEventListener("DOMContentLoaded", function () {
    loadWasteTypes();
});


/* =========================================
   โหลดรายการประเภทขยะ
========================================= */
async function loadWasteTypes() {

    const tableBody =
        document.getElementById("wasteTable");

    if (!tableBody) {
        console.error("ไม่พบ tbody id=wasteTable");
        return;
    }

    tableBody.innerHTML = `
        <tr>
            <td colspan="4">
                กำลังโหลดข้อมูล...
            </td>
        </tr>
    `;

    try {

        const response = await fetch(
            API + "waste_get.php",
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
                "ไม่สามารถโหลดข้อมูลได้"
            );
        }

        tableBody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {

            tableBody.innerHTML = `
                <tr>
                    <td colspan="4">
                        ยังไม่มีประเภทขยะ
                    </td>
                </tr>
            `;

            return;
        }

        data.forEach(function (item, index) {

            const row =
                document.createElement("tr");

            row.innerHTML = `
                <td>
                    ${index + 1}
                </td>

                <td>
                    ${escapeHtml(item.waste_name)}
                </td>

                <td>
                    ${item.points} คะแนน
                </td>

                <td>
                    <button
                        type="button"
                        class="edit-btn"
                    >
                        แก้ไข
                    </button>

                    <button
                        type="button"
                        class="delete-btn"
                    >
                        ลบ
                    </button>
                </td>
            `;


            /* ปุ่มแก้ไข */
            const editButton =
                row.querySelector(".edit-btn");

            editButton.addEventListener(
                "click",
                function () {

                    editWaste(
                        item.waste_id,
                        item.waste_name,
                        item.points
                    );
                }
            );


            /* ปุ่มลบ */
            const deleteButton =
                row.querySelector(".delete-btn");

            deleteButton.addEventListener(
                "click",
                function () {

                    deleteWaste(
                        item.waste_id,
                        item.waste_name
                    );
                }
            );


            tableBody.appendChild(row);
        });

    } catch (error) {

        console.error(error);

        tableBody.innerHTML = `
            <tr>
                <td colspan="4">
                    ${escapeHtml(error.message)}
                </td>
            </tr>
        `;
    }
}


/* =========================================
   เพิ่มประเภทขยะ
========================================= */
const wasteForm =
    document.getElementById("wasteForm");


if (wasteForm) {

    wasteForm.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();


            const wasteName =
                document
                    .getElementById("wasteName")
                    .value
                    .trim();


            const pointsValue =
                document
                    .getElementById("points")
                    .value;


            const points =
                Number(pointsValue);


            /* ตรวจสอบชื่อ */
            if (wasteName === "") {

                alert(
                    "กรุณากรอกประเภทขยะ"
                );

                return;
            }


            /* ตรวจสอบคะแนน */
            if (
                pointsValue.trim() === "" ||
                !Number.isInteger(points) ||
                points < 1
            ) {

                alert(
                    "กรุณากรอกคะแนนเป็นจำนวนเต็มตั้งแต่ 1 ขึ้นไป"
                );

                return;
            }


            try {

                const csrfToken =
                    sessionStorage.getItem(
                        "csrf_token"
                    ) || "";


                const formData =
                    new FormData();


                formData.append(
                    "waste_name",
                    wasteName
                );


                formData.append(
                    "points",
                    points
                );


                const response =
                    await fetch(
                        API + "waste_add.php",
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
                        "เพิ่มข้อมูลไม่สำเร็จ"
                    );
                }


                alert(
                    "เพิ่มประเภทขยะสำเร็จ"
                );


                wasteForm.reset();


                await loadWasteTypes();


            } catch (error) {

                console.error(error);

                alert(
                    error.message ||
                    "เกิดข้อผิดพลาดในการเพิ่มข้อมูล"
                );
            }
        }
    );
}


/* =========================================
   แก้ไขประเภทขยะและคะแนน
========================================= */
async function editWaste(
    wasteId,
    currentName,
    currentPoints
) {

    const newName =
        prompt(
            "กรอกชื่อประเภทขยะ",
            currentName
        );


    if (newName === null) {
        return;
    }


    const trimmedName =
        newName.trim();


    if (trimmedName === "") {

        alert(
            "กรุณากรอกชื่อประเภทขยะ"
        );

        return;
    }


    const newPoints =
        prompt(
            "กรอกคะแนนต่อหน่วย",
            currentPoints
        );


    if (newPoints === null) {
        return;
    }


    const points =
        Number(newPoints);


    if (
        newPoints.trim() === "" ||
        !Number.isInteger(points) ||
        points < 1
    ) {

        alert(
            "กรุณากรอกคะแนนเป็นจำนวนเต็มตั้งแต่ 1 ขึ้นไป"
        );

        return;
    }


    const confirmed =
        confirm(
            `ต้องการแก้ไขข้อมูลเป็น\n\n` +
            `ประเภทขยะ: ${trimmedName}\n` +
            `คะแนนต่อหน่วย: ${points} คะแนน\n\n` +
            `หรือไม่?`
        );


    if (!confirmed) {
        return;
    }


    try {

        const csrfToken =
            sessionStorage.getItem(
                "csrf_token"
            ) || "";


        const formData =
            new FormData();


        formData.append(
            "waste_id",
            wasteId
        );


        formData.append(
            "waste_name",
            trimmedName
        );


        formData.append(
            "points",
            points
        );


        const response =
            await fetch(
                API + "waste_update.php",
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
                "แก้ไขข้อมูลไม่สำเร็จ"
            );
        }


        alert(
            "แก้ไขประเภทขยะและคะแนนสำเร็จ"
        );


        await loadWasteTypes();


    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "เกิดข้อผิดพลาดในการแก้ไขข้อมูล"
        );
    }
}


/* =========================================
   ลบประเภทขยะ
========================================= */
async function deleteWaste(
    wasteId,
    wasteName
) {

    const confirmed =
        confirm(
            `ต้องการลบประเภทขยะ "${wasteName}" หรือไม่?`
        );


    if (!confirmed) {
        return;
    }


    try {

        const csrfToken =
            sessionStorage.getItem(
                "csrf_token"
            ) || "";


        const formData =
            new FormData();


        formData.append(
            "waste_id",
            wasteId
        );


        const response =
            await fetch(
                API + "waste_delete.php",
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
                "ลบข้อมูลไม่สำเร็จ"
            );
        }


        alert(
            "ลบประเภทขยะสำเร็จ"
        );


        await loadWasteTypes();


    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "เกิดข้อผิดพลาดในการลบข้อมูล"
        );
    }
}


/* =========================================
   ออกจากระบบ
========================================= */
async function logout() {

    const confirmed =
        confirm(
            "คุณต้องการออกจากระบบหรือไม่?"
        );


    if (!confirmed) {
        return;
    }


    try {

        const response =
            await fetch(
                API + "logout.php",
                {
                    method: "POST",
                    credentials:
                        "same-origin"
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
                "ออกจากระบบไม่สำเร็จ"
            );
        }


        localStorage.removeItem(
            "user"
        );

        sessionStorage.removeItem(
            "csrf_token"
        );


        window.location.href =
            "login.html";


    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "เกิดข้อผิดพลาดในการออกจากระบบ"
        );
    }
}


/* =========================================
   ป้องกัน HTML Injection
========================================= */
function escapeHtml(text) {

    return String(
        text ?? ""
    )
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