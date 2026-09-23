<?php
require_once "auth.php";

$session = requireLogin();

if (
    !isset($session["role"]) ||
    $session["role"] !== "admin"
) {
    header("Location: ../main/login.html");
    exit();
}

$fullname = htmlspecialchars(
    $session["fullname"] ?? "เจ้าหน้าที่ระบบ"
);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>หน้าหลักเจ้าหน้าที่</title>

    <link rel="stylesheet" href="../main/admin_dashboard.css">
    <link rel="stylesheet" href="../main/theme.css">
</head>

<body>

<header class="top-header">

    <div class="header-content">

        <div>
            <h1>♻️ ระบบสะสมแต้มการแยกขยะ</h1>
            <p>หน้าหลักสำหรับเจ้าหน้าที่</p>
        </div>

        <div class="admin-profile">

            <span>
                👤 <?php echo $fullname; ?>
            </span>

            <button
                type="button"
                id="logoutButton"
                class="logout-button"
            >
                ออกจากระบบ
            </button>

        </div>

    </div>

</header>


<main class="container">

    <!-- ================================
         ยินดีต้อนรับ
    ================================= -->

    <section class="welcome-card">

        <div>

            <p class="welcome-text">
                ยินดีต้อนรับ
            </p>

            <h2>
                <?php echo $fullname; ?>
            </h2>

            <p>
                กรุณาเลือกเมนูที่ต้องการดำเนินการ
            </p>

        </div>

        <div class="welcome-icon">
            👨‍💼
        </div>

    </section>


    <!-- ================================
         เมนูเจ้าหน้าที่
    ================================= -->

    <section class="menu-section">

        <h2>
            เมนูสำหรับเจ้าหน้าที่
        </h2>

        <div class="menu-grid">


            <!-- จัดการประเภทขยะ -->
            <a
                href="../main/manage_waste.html"
                class="menu-card"
            >

                <div class="menu-icon">
                    🗂️
                </div>

                <div>

                    <h3>
                        จัดการประเภทขยะ
                    </h3>

                    <p>
                        เพิ่ม แก้ไข ลบประเภทขยะ
                        และกำหนดคะแนนต่อหน่วย
                    </p>

                </div>

            </a>


            <!-- จัดการบัญชีนักศึกษา -->
            <a
                href="../main/student_manage.html"
                class="menu-card"
            >

                <div class="menu-icon">
                    👥
                </div>

                <div>

                    <h3>
                        จัดการบัญชีนักศึกษา
                    </h3>

                    <p>
                        ตรวจสอบ ระงับ
                        และปลดระงับบัญชีนักศึกษา
                    </p>

                </div>

            </a>


            <!-- ตรวจสอบและอนุมัติ -->
            <a
                href="../main/approve_waste.html"
                class="menu-card"
            >

                <div class="menu-icon">
                    ✅
                </div>

                <div>

                    <h3>
                        ตรวจสอบและอนุมัติ
                    </h3>

                    <p>
                        ตรวจสอบรายการแยกขยะ
                        อนุมัติหรือไม่อนุมัติคะแนน
                    </p>

                </div>

            </a>


            <!-- รายงานข้อมูล -->
            <a
                href="../main/report.html"
                class="menu-card"
            >

                <div class="menu-icon">
                    📊
                </div>

                <div>

                    <h3>
                        รายงานข้อมูล
                    </h3>

                    <p>
                        ดูสรุปรายการแยกขยะ
                        คะแนนสะสม และข้อมูลภาพรวม
                    </p>

                </div>

            </a>


        </div>

    </section>

</main>


<footer>
</footer>


<script>

document
    .getElementById("logoutButton")
    .addEventListener(
        "click",
        async function () {

            const confirmLogout =
                confirm(
                    "คุณต้องการออกจากระบบหรือไม่?"
                );

            if (!confirmLogout) {
                return;
            }


            const button = this;

            button.disabled = true;


            try {

                /*
                    ดึง CSRF Token ล่าสุดก่อน Logout
                */
                const sessionResponse =
                    await fetch(
                        "session.php",
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
                        sessionData.message ||
                        "ไม่สามารถตรวจสอบข้อมูลผู้ใช้ได้"
                    );
                }


                const csrfToken =
                    sessionData.csrf_token;


                /*
                    ส่ง Logout แบบ POST พร้อม CSRF Header
                */
                const response =
                    await fetch(
                        "logout.php",
                        {
                            method: "POST",

                            credentials:
                                "same-origin",

                            headers: {
                                "X-CSRF-Token":
                                    csrfToken
                            }
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


                alert(
                    "ออกจากระบบสำเร็จ"
                );


                window.location.href =
                    "../main/login.html";


            } catch (error) {

                alert(
                    error.message ||
                    "เกิดข้อผิดพลาด"
                );


                button.disabled = false;

            }

        }
    );

</script>

</body>
</html>