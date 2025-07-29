<?php
// เริ่ม session และตรวจสอบการล็อกอินของ admin
session_start();
require_once '../includes/db_connect.php'; // Path นี้จะขึ้นไป 1 ระดับ (จาก /admin/includes/ ไป /admin/) แล้วลงไปที่ /includes/
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> - TAE Esport</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* --- CSS กลางสำหรับ Admin Panel ทั้งหมด --- */
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .sidebar { height: 100%; width: 250px; position: fixed; z-index: 1; top: 0; left: 0; background-color: #343a40; padding-top: 20px; overflow-y: auto; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 1.1rem; color: #adb5bd; display: block; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #495057; }
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background-color: #fff; padding: 10px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .logout-btn { color: #dc3545; text-decoration: none; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle; }
        th { background-color: #e9ecef; }
        .form-container { background-color: #fff; padding: 20px; border-radius: 5px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input[type=text], input[type=number], input[type=file], input[type=datetime-local], select, textarea { width: 100%; padding: 8px; margin: 6px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 style="color: white; text-align: center;">TAE Esport Admin</h3>
    <a href="dashboard.php" class="<?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
    <a href="manage_teams.php" class="<?php echo ($active_page == 'teams') ? 'active' : ''; ?>">จัดการทีม</a>
    <a href="manage_players.php" class="<?php echo ($active_page == 'players') ? 'active' : ''; ?>">จัดการนักกีฬา</a>
    <a href="manage_matches.php" class="<?php echo ($active_page == 'matches') ? 'active' : ''; ?>">จัดการตารางแข่ง</a>
    <a href="manage_news.php" class="<?php echo ($active_page == 'news') ? 'active' : ''; ?>">จัดการข่าวสาร</a>
    <a href="manage_comments.php" class="<?php echo ($active_page == 'comments') ? 'active' : ''; ?>">จัดการคอมเมนต์</a>
    <a href="manage_gallery.php" class="<?php echo ($active_page == 'gallery') ? 'active' : ''; ?>">จัดการแกลเลอรี่</a>
    <a href="manage_sponsors.php" class="<?php echo ($active_page == 'sponsors') ? 'active' : ''; ?>">จัดการผู้สนับสนุน</a>
    <a href="manage_news.php" class="<?php echo ($active_page == 'news') ? 'active' : ''; ?>">จัดการข่าวสาร</a>
    <a href="manage_meta.php" class="<?php echo ($active_page == 'meta') ? 'active' : ''; ?>">จัดการ Meta</a>    
</div>

<div class="main-content">
    <div class="header">
        <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></strong></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>