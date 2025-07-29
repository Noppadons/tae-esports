<?php
// /admin/delete_match.php
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
    $match_id = trim($_GET['id']); // เก็บค่าลงในตัวแปรเพื่อความชัดเจน

    $sql = "DELETE FROM matches WHERE id = :id"; // ใช้ named placeholder :id
    
    try {
        $stmt = $conn->prepare($sql);
        // ผูกค่าแบบ integer (PDO::PARAM_INT)
        $stmt->bindParam(':id', $match_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // PDO ไม่จำเป็นต้องมี $stmt->close() หรือ $conn->close() เหมือน mysqli
        // PDO statements จะถูกทำลายเมื่อหมด scope หรือเมื่อ Script จบลง
        // การเชื่อมต่อ PDO ($conn) จะถูกปิดโดยอัตโนมัติเมื่อ Script จบ
        
    } catch (PDOException $e) {
        // จัดการข้อผิดพลาดที่อาจเกิดขึ้นระหว่างการลบข้อมูล
        error_log("Error deleting match from database: " . $e->getMessage());
        // คุณสามารถเพิ่มการแจ้งเตือนผู้ใช้ เช่น $_SESSION['error_message'] หรือ redirect ไปหน้า error ได้
    }
}

// Redirect กลับไปหน้า manage_matches.php หลังจากเสร็จสิ้น (ไม่ว่าจะลบสำเร็จหรือไม่ก็ตาม)
header("location: manage_matches.php");
exit();
?>