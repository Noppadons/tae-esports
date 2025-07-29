<?php
// /includes/header.php
session_start();
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAE Esport - Official Website</title>
    <style>
        /* Basic CSS Reset & Body Style */
        body {
            font-family: 'Kanit', sans-serif;
            margin: 0;
            background-color: #121212;
            color: #e0e0e0;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        /* Header & Navigation */
        header {
            background-color: #1f1f1f;
            padding: 1rem 0;
            border-bottom: 2px solid #007bff;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }
        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
        }
        nav ul li {
            margin-left: 25px;
        }
        nav ul li a {
            color: #e0e0e0;
            text-decoration: none;
            font-size: 1.1rem;
            transition: color 0.3s;
            padding: 5px 0;
        }
        nav ul li a:hover, nav ul li a.active {
            color: #007bff;
        }
        /* Section Styling */
        .section {
            padding: 40px 0;
        }
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 30px;
            color: #fff;
        }
        /* Footer */
        footer {
            background-color: #1f1f1f;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid #333;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <nav>
        <a href="index.php" class="logo">TAE ESPORT</a>
        <ul>
            <li><a href="index.php">หน้าหลัก</a></li>
            <li><a href="players.php">นักกีฬา</a></li>
            <li><a href="matches.php">ตารางแข่ง</a></li>
            <li><a href="news.php">ข่าวสาร</a></li>
            <li><a href="meta.php">แนะนำ Meta</a></li>
            <li><a href="gallery.php">แกลเลอรี่</a></li>
            <li><a href="sponsors.php">ผู้สนับสนุน</a></li>
             <li><a href="contact.php">ติดต่อเรา</a></li>
            
            <?php if (isset($_SESSION["user_id"])): ?>
                <li><a href="profile.php" style="color:#00aaff; font-weight: bold;"><?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                <li><a href="logout.php" style="color:#dc3545;">ออกจากระบบ</a></li>
            <?php else: ?>
                <li><a href="login.php">เข้าสู่ระบบ</a></li>
                <li><a href="register.php">สมัครสมาชิก</a></li>
            <?php endif; ?>
            </ul>
    </nav>
</header>