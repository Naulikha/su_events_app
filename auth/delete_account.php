<?php
// REFERENCE: Sessions (Lecture)
session_start();
require_once '../config/db.php';

// If they aren't logged in, they can't delete anything
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ensure the request came from a POST form submit to prevent accidental deletions via URL
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['user_id'];

    // REFERENCE: PHP_MYSQL_PREPARED_STATEMENTS (Lecture)
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Successfully deleted from database. Now destroy their session!
        session_unset();
        session_destroy();
        
        // Send them back to the register page with a URL parameter so we can show a message
        header("Location: register.php?deleted=true");
        exit();
    } else {
        echo "Error deleting account. Please contact the Admin.";
    }
    
    $stmt->close();
} else {
    // If someone tries to just type 'delete_account.php' in the URL, kick them out
    header("Location: ../index.php");
    exit();
}
?>