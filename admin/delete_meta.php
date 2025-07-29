<?php
// /admin/delete_meta.php
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
    $guide_id = trim($_GET['id']); // เก็บค่าลงในตัวแปรเพื่อความชัดเจน

    $sql = "DELETE FROM meta_guides WHERE id = :id"; // ใช้ named placeholder :id
    
    try {
        $stmt = $conn->prepare($sql);
        // ผูกค่าแบบ integer (PDO::PARAM_INT)
        $stmt->bindParam(':id', $guide_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // PDO ไม่จำเป็นต้องมี $stmt->close() หรือ $conn->close() เหมือน mysqli
        
    } catch (PDOException $e) {
        // จัดการข้อผิดพลาดที่อาจเกิดขึ้นระหว่างการลบข้อมูล
        error_log("Error deleting meta guide from database: " . $e->getMessage());
        // คุณสามารถเพิ่มการแจ้งเตือนผู้ใช้ หรือ redirect ไปหน้า error ได้
    }
}

// Redirect กลับไปหน้า manage_meta.php หลังจากเสร็จสิ้น
header("location: manage_meta.php");
exit();
?>