<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["avatar"])) {
    $user_id = $_SESSION['user_id'];
    
    // ตรวจสอบว่ามี error ในการอัปโหลดหรือไม่
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // กำหนดโฟลเดอร์ที่จะเก็บไฟล์
        $target_dir = "assets/img/avatars/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน เพื่อป้องกันการเขียนทับ
        $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $new_filename = $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // (Optional) ลบรูปโปรไฟล์เก่า ถ้ามี
        $sql_select = "SELECT avatar_url FROM users WHERE id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $user_id);
        $stmt_select->execute();
        $old_avatar = $stmt_select->get_result()->fetch_assoc();
        if ($old_avatar && !empty($old_avatar['avatar_url'])) {
            if (file_exists($old_avatar['avatar_url'])) {
                unlink($old_avatar['avatar_url']);
            }
        }

        // ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์เป้าหมาย
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            
            // อัปเดตฐานข้อมูลด้วย URL ใหม่
            $sql_update = "UPDATE users SET avatar_url = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $target_file, $user_id);
            $stmt_update->execute();
        }
    }
}

// ไม่ว่าจะสำเร็จหรือไม่ ให้กลับไปหน้าโปรไฟล์
header("location: profile.php");
exit();
?>