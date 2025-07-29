<?php
// /admin/api_get_heroes.php

// ไม่ต้องมี session check หรือ HTML ใดๆ ทั้งสิ้น
require_once '../includes/api_helpers.php';

// ดึงข้อมูลฮีโร่
$dota_heroes = getDota2Heroes();

// ตั้งค่า Header ให้เบราว์เซอร์รู้ว่าเป็นข้อมูล JSON
header('Content-Type: application/json');

// ส่งข้อมูลกลับไปในรูปแบบ JSON
echo json_encode($dota_heroes);
?>