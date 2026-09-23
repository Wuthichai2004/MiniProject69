const API = "../api/";

document
    .getElementById("registerForm")
    .addEventListener("submit", async function (event) {

        event.preventDefault();

        const fullname = document
            .getElementById("fullname")
            .value
            .trim();

        const username = document
            .getElementById("username")
            .value
            .trim();

        const password = document
            .getElementById("password")
            .value;

        const role = "student";

        const formData = new FormData();

        formData.append("fullname", fullname);
        formData.append("username", username);
        formData.append("password", password);
        formData.append("role", role);

        try {

            const response = await fetch(
                API + "register.php",
                {
                    method: "POST",
                    body: formData
                }
            );

            const text = await response.text();

            console.log("PHP Response:", text);

            let data;

            try {
                data = JSON.parse(text);
            } catch (error) {
                console.error(
                    "PHP ส่งข้อมูลไม่ใช่ JSON:",
                    text
                );

                throw new Error(
                    "PHP ไม่ได้ส่ง JSON กลับมา"
                );
            }

            alert(data.message);

            if (data.success) {
                window.location.href = "login.html";
            }

        } catch (error) {

            console.error(error);

            alert(
                "เกิดข้อผิดพลาดในการสมัครสมาชิก\n" +
                error.message
            );
        }
    });
