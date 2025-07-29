<?php
session_start();
require_once 'includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    die("Database connection failed. Please check includes/db_connect.php");
}

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["avatar"])) {
    $user_id = $_SESSION['user_id'];
    
    // ตรวจสอบว่ามี error ในการอัปโหลดหรือไม่
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // **ข้อควรระวังเรื่องการจัดการไฟล์บน Render (Stateless Service):**
        // การบันทึกไฟล์โดยตรงบน Server ของ Render (assets/img/avatars/)
        // จะไม่ยั่งยืนใน Production เนื่องจากไฟล์จะหายไปเมื่อ Container ถูก Restart/rebuild
        // ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3, Cloudinary) สำหรับไฟล์ที่ผู้ใช้อัปโหลด
        // แต่สำหรับตอนนี้ โค้ดจะถูกปรับให้ทำงานตามโครงสร้างเดิมโดยใช้ Path สัมพัทธ์

        // กำหนดโฟลเดอร์ที่จะเก็บไฟล์
        $target_dir = "assets/img/avatars/";
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) { // ตั้งค่า permission ที่เหมาะสม
                error_log("Failed to create directory: " . $target_dir);
                // อาจจะจัดการ error หรือแจ้งผู้ใช้
            }
        }
        
        // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกัน เพื่อป้องกันการเขียนทับ
        $file_extension = pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION);
        $new_filename = $user_id . '_' . time() . '.' . $file_extension;
        $target_file_path = $target_dir . $new_filename; // นี่คือ Path จริงบน Server
        $avatar_url_db = $target_dir . $new_filename; // นี่คือ Path ที่จะเก็บใน Database

        // (Optional) ลบรูปโปรไฟล์เก่า ถ้ามี
        $sql_select_old_avatar = "SELECT avatar_url FROM users WHERE id = :id";
        try {
            $stmt_select_old_avatar = $conn->prepare($sql_select_old_avatar);
            $stmt_select_old_avatar->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt_select_old_avatar->execute();
            $old_avatar = $stmt_select_old_avatar->fetch(PDO::FETCH_ASSOC);

            if ($old_avatar && !empty($old_avatar['avatar_url'])) {
                // ตรวจสอบว่าไฟล์เก่าอยู่จริงบน Server ก่อนลบ
                if (file_exists($old_avatar['avatar_url'])) {
                    unlink($old_avatar['avatar_url']);
                }
            }
        } catch (PDOException $e) {
            error_log("Database error selecting old avatar URL: " . $e->getMessage());
            // ไม่ถึงกับ fatal error แค่ไม่สามารถลบรูปเก่าได้
        }

        // ย้ายไฟล์ที่อัปโหลดไปยังโฟลเดอร์เป้าหมาย
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file_path)) {
            
            // อัปเดตฐานข้อมูลด้วย URL ใหม่
            $sql_update_avatar = "UPDATE users SET avatar_url = :avatar_url WHERE id = :id";
            try {
                $stmt_update_avatar = $conn->prepare($sql_update_avatar);
                $stmt_update_avatar->bindParam(':avatar_url', $avatar_url_db, PDO::PARAM_STR);
                $stmt_update_avatar->bindParam(':id', $user_id, PDO::PARAM_INT);
                $stmt_update_avatar->execute();

                // อัปเดต avatar_url ใน Session ด้วย (ถ้าใช้ใน header)
                $_SESSION['avatar_url'] = $avatar_url_db;

            } catch (PDOException $e) {
                error_log("Database error updating avatar URL: " . $e->getMessage());
                // อาจจะแสดงข้อความ error หรือจัดการตามความเหมาะสม
            }
        } else {
            error_log("Failed to move uploaded avatar file for user ID: " . $user_id);
            // อาจจะแสดงข้อความ error หรือจัดการตามความเหมาะสม
        }
    } else {
        // จัดการข้อผิดพลาดในการอัปโหลดไฟล์ (เช่น ขนาดเกิน, ไม่มีไฟล์)
        error_log("Avatar upload error for user ID " . $user_id . ": " . $_FILES['avatar']['error']);
        // คุณอาจจะเพิ่มข้อความ error ให้ผู้ใช้เห็น
    }
}

// ไม่ว่าจะสำเร็จหรือไม่ ให้กลับไปหน้าโปรไฟล์
header("location: profile.php");
exit();
?>