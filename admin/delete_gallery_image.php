<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $image_id = trim($_GET['id']);
    
    // Get image path to delete the file
    $sql_select = "SELECT image_url FROM gallery WHERE id = ?";
    if ($stmt_select = $conn->prepare($sql_select)) {
        $stmt_select->bind_param("i", $image_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['image_url']) && file_exists("../" . $row['image_url'])) {
                unlink("../" . $row['image_url']);
            }
        }
    }
    
    // Delete the record from database
    $sql_delete = "DELETE FROM gallery WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $image_id);
        $stmt_delete->execute();
    }
}
header("location: manage_gallery.php");
exit();
?>