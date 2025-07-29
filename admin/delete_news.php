<?php
// /admin/delete_news.php
session_start();
// ตรวจสอบเส้นทางของ db_connect.php ว่าถูกต้อง
require_once '../includes/db_connect.php';

// ตรวจสอบว่า $conn เป็น PDO object ที่เชื่อมต่อแล้ว
if (!isset($conn) || !$conn instanceof PDO) {
    // จัดการข้อผิดพลาดถ้าการเชื่อมต่อ DB ล้มเหลว
    die("Database connection failed. Please check ../includes/db_connect.php");
}

// ตรวจสอบสิทธิ์การเข้าถึง (Admin)
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่ามีค่า id ส่งมาหรือไม่
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $news_id = trim($_GET['id']); // เก็บค่าลงในตัวแปรเพื่อความชัดเจน

    // --- 1. Get the image path to delete the file from server ---
    $sql_select = "SELECT image_url FROM news WHERE id = :id";
    try {
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bindParam(':id', $news_id, PDO::PARAM_INT); // ผูกค่าแบบ integer
        $stmt_select->execute();

        $row = $stmt_select->fetch(PDO::FETCH_ASSOC); // ใช้ fetch(PDO::FETCH_ASSOC)

        if ($row) { // ตรวจสอบว่าพบข่าวหรือไม่
            // **ข้อควรระวัง:** การลบไฟล์แบบนี้บน Render Web Service อาจไม่ยั่งยืน
            // เนื่องจาก Render Web Service เป็น Stateless (ไฟล์ที่เขียนไปจะหายไปเมื่อ Container ถูก restart/rebuild)
            // ถ้าคุณมีการอัปโหลดไฟล์ในโปรเจกต์ ควรพิจารณาใช้ Cloud Storage (เช่น AWS S3)
            // แต่สำหรับตอนนี้เราจะแก้ไขโค้ดให้ทำงานได้ตามโครงสร้างเดิมก่อน
            $file_path = "../" . $row['image_url'];
            if (!empty($row['image_url']) && file_exists($file_path)) {
                // เพิ่มความปลอดภัย: ตรวจสอบไม่ให้ลบไฟล์นอกเหนือจากที่ควร
                // ตัวอย่าง: if (strpos(realpath($file_path), realpath('../uploads')) === 0) { ... }
                unlink($file_path); // Delete the image file
            }
        }
        // PDO ไม่จำเป็นต้องมี $stmt_select->close();
    } catch (PDOException $e) {
        error_log("Error selecting news image for deletion: " . $e->getMessage());
        // สามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }

    // --- 2. Delete the record from the database ---
    $sql_delete = "DELETE FROM news WHERE id = :id";
    try {
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $news_id, PDO::PARAM_INT); // ผูกค่าแบบ integer
        $stmt_delete->execute();
        // PDO ไม่จำเป็นต้องมี $stmt_delete->close();
    } catch (PDOException $e) {
        error_log("Error deleting news record from database: " . $e->getMessage());
        // สามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }
}

// PDO ไม่จำเป็นต้องมี $conn->close();
// Redirect back to the news management page
header("location: manage_news.php");
exit();
?>