<?php
// REFERENCE: Sessions (Lecture)
session_start();

// THE BOUNCER: Only allow Organisers (Society Members)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organiser') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organiser Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! (Organiser)</h2>
    <p>Here you will be able to Create, Update, and Delete your Society Events.</p>

    <!-- The Delete Account Feature from your notes! -->
    <form action="delete_account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account?');">
        <button type="submit" style="color:red;">Delete My Account</button>
    </form>
    <br>
    <a href="../auth/logout.php">Logout</a>
</body>
</html>