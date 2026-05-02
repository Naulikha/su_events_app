<?php
// REFERENCE: Sessions (Lecture)
session_start();

// THE BOUNCER: If they are not logged in OR they are not an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    // Note implemented: Destroy session if not Admin trying to access Admin panel
    session_destroy(); 
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SU Events</title>
</head>
<body>
    <!-- REFERENCE: HTML Entities (Lecture) -> securely printing the name -->
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! (Admin)</h2>
    <p>This is the Admin Control Panel. Only Admins can see this.</p>
    
    <a href="../auth/logout.php">Logout</a>
</body>
</html>