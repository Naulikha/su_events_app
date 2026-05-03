<?php
// REFERENCE: Sessions (Lecture)
session_start();

// THE BOUNCER: Kick out anyone who isn't an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// REFERENCE: Working with Databases (Lecture)
require_once '../config/db.php';

$success = "";
$error = "";

// --- HANDLE USER DELETION (Admin Power) ---
if (isset($_GET['delete_user'])) {
    $target_user_id = (int)$_GET['delete_user'];
    
    // Protection: Prevent the Admin from accidentally deleting themselves!
    if ($target_user_id === $_SESSION['user_id']) {
        $error = "You cannot delete your own Admin account!";
    } else {
        // REFERENCE: PHP_MYSQL_PREPARED_STATEMENTS (Lecture)
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $target_user_id);
        
        if ($stmt->execute()) {
            $success = "User successfully deleted.";
        } else {
            $error = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// --- FETCH ALL USERS ---
// We want to see newest users first
$sql = "SELECT user_id, full_name, student_id, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - User Management</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f9; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #333; color: white; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Admin Dashboard: User Management</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>. You have full system access.</p>
    
    <a href="../auth/logout.php">Logout</a> | <a href="../index.php">View Public Events Page</a>
    <hr>

    <?php if(!empty($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Student ID</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Action</th>
        </tr>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while($user = $result->fetch_assoc()): ?>
                <tr>
                    <!-- REFERENCE: HTML Entities (Lecture) -> secure output -->
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><strong><?php echo $user['role']; ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                            <a href="?delete_user=<?php echo $user['user_id']; ?>" class="btn-delete" onclick="return confirm('Delete this user? This will also delete all their events and bookings!');">Delete</a>
                        <?php else: ?>
                            <em>Your Account</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No users found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>