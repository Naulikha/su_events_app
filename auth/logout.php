<?php
// REFERENCE: Sessions (Lecture)
session_start();
session_unset();    // Remove all session variables
session_destroy();  // Destroy the session
header("Location: login.php"); // Send them back to login
exit();
?>