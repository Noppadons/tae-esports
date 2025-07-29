<?php
// /admin/delete_match.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $sql = "DELETE FROM matches WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", trim($_GET['id']));
        $stmt->execute();
        $stmt->close();
    }
}
$conn->close();
header("location: manage_matches.php");
exit();
?>