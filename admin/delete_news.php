<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $news_id = trim($_GET['id']);

    // 1. Get the image path to delete the file from server
    $sql_select = "SELECT image_url FROM news WHERE id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $news_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['image_url']) && file_exists("../" . $row['image_url'])) {
                unlink("../" . $row['image_url']); // Delete the image file
            }
        }
        $stmt_select->close();
    }

    // 2. Delete the record from the database
    $sql_delete = "DELETE FROM news WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $news_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
}

// Redirect back to the news management page
$conn->close();
header("location: manage_news.php");
exit();
?>