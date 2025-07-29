<?php
session_start();
// ตรวจสอบเส้นทางของ db_connect.php ว่าถูกต้องเมื่อ Deploy บน Render
// ถ้าไฟล์นี้อยู่ใน admin/ แล้ว db_connect.php อยู่ใน includes/ (ซึ่งอยู่ข้างนอก admin/)
// ก็ใช้ ../includes/db_connect.php ถูกต้องแล้วครับ
require_once '../includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    // จัดการข้อผิดพลาดถ้าการเชื่อมต่อ DB ล้มเหลว
    // ใน production อาจจะโยน exception หรือ redirect ไปหน้า error
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// ตรวจสอบสิทธิ์การเข้าถึง (Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่ามีค่า id ส่งมาหรือไม่
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $image_id = trim($_GET['id']);

    // --- ส่วนที่ 1: ดึง image_url เพื่อลบไฟล์รูปภาพ ---
    $sql_select = "SELECT image_url FROM gallery WHERE id = :id";
    try {
        // ใช้ prepare และ execute สำหรับ PDO
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bindParam(':id', $image_id, PDO::PARAM_INT); // ผูกค่าแบบ integer
        $stmt_select->execute();

        // ใช้ fetch(PDO::FETCH_ASSOC) เพื่อดึงผลลัพธ์
        $row = $stmt_select->fetch(PDO::FETCH_ASSOC);

        if ($row) { // ตรวจสอบว่าพบรูปภาพหรือไม่
            // ตรวจสอบว่า image_url ไม่ว่างเปล่าและไฟล์มีอยู่จริง
            // **ข้อควรระวัง:** การลบไฟล์แบบนี้บน Render Web Service อาจไม่ยั่งยืน
            // เนื่องจาก Render Web Service เป็น Stateless (ไฟล์ที่เขียนไปจะหายไปเมื่อ Container ถูก restart/rebuild)
            // ถ้าคุณมีการอัปโหลดไฟล์ในโปรเจกต์ ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3)
            // แต่สำหรับตอนนี้เราจะแก้ไขโค้ดให้ทำงานได้ตามโครงสร้างเดิมก่อน
            $file_path = "../" . $row['image_url'];
            if (!empty($row['image_url']) && file_exists($file_path)) {
                // ตรวจสอบให้แน่ใจว่าเป็นไฟล์จริงๆ และไม่อยู่ใน directory สำคัญ
                // เพิ่มความปลอดภัยเล็กน้อย: ตรวจสอบไม่ให้ลบไฟล์นอกเหนือจากที่ควร
                // ตัวอย่าง: if (strpos(realpath($file_path), realpath('../uploads')) === 0) { ... }
                // แต่สำหรับตอนนี้ ผมจะคงตามโครงสร้างเดิมของคุณ
                unlink($file_path);
            }
        }
    } catch (PDOException $e) {
        error_log("Error selecting image_url for deletion: " . $e->getMessage());
        // สามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }
    
    // --- ส่วนที่ 2: ลบ Record จาก Database ---
    $sql_delete = "DELETE FROM gallery WHERE id = :id";
    try {
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $image_id, PDO::PARAM_INT); // ผูกค่าแบบ integer
        $stmt_delete->execute();
    } catch (PDOException $e) {
        error_log("Error deleting image from database: " . $e->getMessage());
        // สามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }
}

// Redirect กลับไปหน้า manage_gallery.php หลังจากเสร็จสิ้น
header("location: manage_gallery.php");
exit();
?>