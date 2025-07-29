<?php
session_start();
$_SESSION = array(); // ล้างค่าใน session ทั้งหมด
session_destroy(); // ทำลาย session
header("location: index.php"); // กลับไปหน้าแรก
exit;
?>