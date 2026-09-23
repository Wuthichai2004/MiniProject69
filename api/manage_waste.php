<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../main/login.html");
    exit();
}

header("Location: ../main/manage_waste.html");
exit();

$conn = new mysqli("localhost", "root", "", "waste_sorting");

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ");
}

$result = $conn->query("SELECT * FROM waste_types ORDER BY waste_id ASC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการประเภทขยะ</title>
</head>
<body>

    <h2>จัดการประเภทขยะ</h2>

    <a href="add_waste.php">เพิ่มประเภทขยะ</a>
    <br><br>

    <table border="1" cellpadding="10">
        <tr>
            <th>รหัส</th>
            <th>ประเภทขยะ</th>
            <th>คะแนนต่อหน่วย</th>
            <th>จัดการ</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row["waste_id"]; ?></td>

                <td>
                    <?php echo htmlspecialchars($row["waste_name"]); ?>
                </td>

                <td><?php echo $row["points"]; ?></td>

                <td>
                    <a href="edit_waste.php?id=<?php echo $row["waste_id"]; ?>">
                        แก้ไข
                    </a>

                    |

                    <a
                        href="delete_waste.php?id=<?php echo $row["waste_id"]; ?>"
                        onclick="return confirm('ต้องการลบข้อมูลนี้หรือไม่?')"
                    >
                        ลบ
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>

    <br>
    <a href="admin_dashboard.php">กลับหน้าหลัก</a>

</body>
</html>
