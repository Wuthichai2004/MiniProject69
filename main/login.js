const API = "../api/";

const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;

    try {
        const response = await fetch(API + "login.php", {
            method: "POST",
            body: new FormData(this),
            credentials: "same-origin"
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || "เข้าสู่ระบบไม่สำเร็จ"
            );
        }

        localStorage.setItem(
            "user",
            JSON.stringify(data.user)
        );

        sessionStorage.setItem(
            "csrf_token",
            data.csrf_token
        );

        if (data.user.role === "admin") {
            window.location.href =
                "../api/admin_dashboard.php";
        } else {
            window.location.href =
                "record_waste.html";
        }

    } catch (error) {
        alert(
            error.message ||
            "เกิดข้อผิดพลาดในการเข้าสู่ระบบ"
        );
    } finally {
        button.disabled = false;
    }
});