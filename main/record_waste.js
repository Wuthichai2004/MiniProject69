const API = "../api/";
let csrfToken = sessionStorage.getItem("csrf_token") || "";

async function apiFetch(path, options = {}) {
    options.headers = { ...(options.headers || {}), ...(csrfToken ? { "X-CSRF-Token": csrfToken } : {}) };
    const response = await fetch(API + path, options);
    let data;
    try { data = await response.json(); } catch (_) { throw new Error("การตอบกลับจากเซิร์ฟเวอร์ไม่ถูกต้อง"); }
    if (!response.ok) {
        if (response.status === 401) window.location.href = "login.html";
        throw new Error(data.message || "เกิดข้อผิดพลาด");
    }
    return data;
}

async function verifySession() {
    const data = await apiFetch("session.php");
    csrfToken = data.csrf_token;
    sessionStorage.setItem("csrf_token", csrfToken);
    if (data.user.role === "admin") { window.location.href = "manage_waste.html"; return false; }
    document.getElementById("currentUser").textContent = `ผู้ใช้งาน: ${data.user.fullname}`;
    return true;
}

async function loadWasteTypes() {
    const data = await apiFetch("waste_get.php");
    const select = document.getElementById("waste_id");
    data.forEach(waste => {
        const option = document.createElement("option");
        option.value = waste.waste_id;
        option.dataset.points = waste.points;
        option.textContent = `${waste.waste_name} (${waste.points} คะแนน/หน่วย)`;
        select.appendChild(option);
    });
}

function updatePointsEstimate() {
    const select = document.getElementById("waste_id");
    const quantity = Number(document.getElementById("quantity").value);
    const points = Number(select.selectedOptions[0]?.dataset.points || 0);
    const estimate = document.getElementById("pointsEstimate");
    if (points > 0 && Number.isInteger(quantity) && quantity > 0) {
        document.getElementById("estimatedPoints").textContent = String(points * quantity);
        estimate.hidden = false;
    } else {
        estimate.hidden = true;
    }
}

document.getElementById("waste_id").addEventListener("change", updatePointsEstimate);
document.getElementById("quantity").addEventListener("input", updatePointsEstimate);

document.getElementById("image").addEventListener("change", function () {
    const preview = document.getElementById("imagePreview");
    preview.replaceChildren();
    if (!this.files[0]) return;
    if (this.files[0].size > 5 * 1024 * 1024) { alert("รูปภาพต้องมีขนาดไม่เกิน 5 MB"); this.value = ""; return; }
    const image = document.createElement("img");
    const objectUrl = URL.createObjectURL(this.files[0]);
    image.src = objectUrl;
    image.addEventListener("load", () => URL.revokeObjectURL(objectUrl), { once: true });
    preview.appendChild(image);
});

document.getElementById("wasteRecordForm").addEventListener("submit", async function (event) {
    event.preventDefault();
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
        const data = await apiFetch("record_waste.php", { method: "POST", body: new FormData(this) });
        alert(data.message); this.reset(); document.getElementById("imagePreview").replaceChildren(); updatePointsEstimate();
    } catch (error) { alert(error.message); }
    finally { button.disabled = false; }
});

async function logout() {
    if (!confirm("คุณต้องการออกจากระบบหรือไม่?")) return;
    try { await apiFetch("logout.php", { method: "POST" }); } catch (_) {}
    localStorage.removeItem("user"); sessionStorage.removeItem("csrf_token"); window.location.href = "login.html";
}

(async () => {
    try { if (await verifySession()) await loadWasteTypes(); }
    catch (error) { alert(error.message); }
})();
