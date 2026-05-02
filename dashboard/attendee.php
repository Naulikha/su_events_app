<?php
session_start();

// THE BOUNCER: Must be logged in as an Attendee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Attendee') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
    <p>Here you will see the events you have booked tickets for.</p>

    <!-- The Delete Account Feature -->
    <form action="delete_account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account?');">
        <button type="submit" style="color:red;">Delete My Account</button>
    </form>
    <br>
    <a href="../auth/logout.php">Logout</a>
</body>
</html>