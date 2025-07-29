<?php
// /admin/delete_player.php
session_start();
require_once '../includes/db_connect.php';

// Security check
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("location: login.php");
    exit;
}

// Check if ID is provided
if (isset($_GET['id']) && !empty(trim($_GET['id']))) {
    $player_id = trim($_GET['id']);
    
    $sql = "DELETE FROM players WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $player_id);
        
        if ($stmt->execute()) {
            // Redirect to manage players page after successful deletion
            header("location: manage_players.php");
            exit();
        } else {
            echo "มีบางอย่างผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
        $stmt->close();
    }
} else {
    // Redirect if ID is not provided
    header("location: manage_players.php");
    exit();
}

$conn->close();
?>