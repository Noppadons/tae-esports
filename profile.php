<?php
require_once 'includes/header.php';

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// ดึงข้อมูลล่าสุดของผู้ใช้จากฐานข้อมูล (รวมถึง avatar_url)
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, avatar_url FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// เก็บ URL รูปภาพไว้ใน session เพื่อใช้ใน header ได้ (ถ้าต้องการ)
$_SESSION['avatar_url'] = $user['avatar_url'];

?>
<style>
    .profile-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 40px;
        max-width: 900px;
        margin: 40px auto;
        background-color: #1f1f1f;
        padding: 30px;
        border-radius: 8px;
    }
    .profile-sidebar {
        text-align: center;
    }
    .profile-avatar {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #007bff;
        margin-bottom: 20px;
    }
    .upload-form label {
        background-color: #007bff;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-block;
        font-size: 0.9rem;
    }
    .upload-form input[type="file"] {
        display: none; /* ซ่อน input file เริ่มต้น */
    }
    .upload-form button {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
    }
    .profile-main h1 {
        margin-top: 0;
    }
</style>

<div class="container">
    <div class="profile-container">
        <aside class="profile-sidebar">
            <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'assets/img/default_avatar.png'; ?>" alt="Profile Avatar" class="profile-avatar">
            
            <form action="upload_avatar.php" method="post" enctype="multipart/form-data" class="upload-form">
                <label for="avatar-upload">เลือกรูปภาพ</label>
                <input type="file" name="avatar" id="avatar-upload" accept="image/*">
                <button type="submit">อัปโหลด</button>
            </form>
            
        </aside>
        <main class="profile-main">
            <h1>โปรไฟล์ของ <?php echo htmlspecialchars($user["username"]); ?></h1>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user["email"]); ?></p>
            <p>ยินดีต้อนรับสู่พื้นที่สำหรับแฟนคลับ! ในอนาคตคุณจะสามารถปรับแต่งโปรไฟล์และเข้าถึงเนื้อหาพิเศษได้ที่นี่</p>
            <br>
            <a href="logout.php" style="color: #dc3545;">ออกจากระบบ</a>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>