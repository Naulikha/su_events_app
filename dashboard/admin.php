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

// --- HANDLE EVENT DELETION (Admin Power!) ---
if (isset($_GET['delete_event'])) {
    $target_event_id = (int)$_GET['delete_event'];
    
    // REFERENCE: PHP_MYSQL_PREPARED_STATEMENTS (Lecture)
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $target_event_id);
    if ($stmt->execute()) {
        $success = "Event successfully deleted.";
    } else {
        $error = "Failed to delete event.";
    }
    $stmt->close();
}

// --- FETCH ALL USERS ---
//new users first
$sql_users = "SELECT user_id, full_name, student_id, email, role, created_at FROM users ORDER BY created_at DESC";
$result_users = $conn->query($sql_users);

// --- FETCH ALL EVENTS (Using JOIN to get the Organiser's Name) ---
// REFERENCE: Working with Databases (Lecture)
$sql_events = "SELECT e.event_id, e.title, e.society_name, e.event_date, e.max_capacity, u.full_name AS organiser_name 
               FROM events e 
               JOIN users u ON e.organiser_id = u.user_id 
               ORDER BY e.event_date ASC";
$result_events = $conn->query($sql_events);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - System Management</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f9; }
        .table-container { margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 10px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #333; color: white; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 14px;}
        .success { color: green; font-weight: bold; background: #d4edda; padding: 10px; border-radius: 5px; border: 1px solid #c3e6cb;}
        .error { color: red; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 5px; border: 1px solid #f5c6cb;}
    </style>
</head>
<body>
    <h2>Admin Dashboard: System Management</h2>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>. You have full system access.</p>
    
    <a href="../auth/logout.php">Logout</a> | <a href="../index.php">View Public Events Page</a>
    <hr>

    <?php if(!empty($success)) echo "<div class='success'>$success</div><br>"; ?>
    <?php if(!empty($error)) echo "<div class='error'>$error</div><br>"; ?>

    <!-- SECTION 1: USER MANAGEMENT -->
    <div class="table-container">
        <h3>Registered Users</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Action</th>
            </tr>
            <?php if ($result_users->num_rows > 0): ?>
                <?php while($user = $result_users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><strong><?php echo $user['role']; ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['user_id'] !== $_SESSION['user_id']): ?>
                                <a href="?delete_user=<?php echo $user['user_id']; ?>" class="btn-delete" onclick="return confirm('Delete this user? This will also wipe all their events and bookings!');">Delete User</a>
                            <?php else: ?>
                                <em>Your Account</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No users found.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- SECTION 2: EVENT MANAGEMENT -->
    <div class="table-container">
        <h3>All Society Events</h3>
        <table>
            <tr>
                <th>Event ID</th>
                <th>Title</th>
                <th>Society</th>
                <th>Organiser Name</th>
                <th>Date</th>
                <th>Capacity</th>
                <th>Action</th>
            </tr>
            <?php if ($result_events->num_rows > 0): ?>
                <?php while($event = $result_events->fetch_assoc()): ?>
                    <tr>
                        <!-- REFERENCE: HTML Entities (Lecture) -> secure output -->
                        <td><?php echo $event['event_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($event['society_name']); ?></td>
                        <td><?php echo htmlspecialchars($event['organiser_name']); ?></td>
                        <td><?php echo date('M d, Y @ g:i A', strtotime($event['event_date'])); ?></td>
                        <td><?php echo $event['max_capacity']; ?></td>
                        <td>
                            <a href="?delete_event=<?php echo $event['event_id']; ?>" class="btn-delete" onclick="return confirm('Delete this event? All student bookings for this event will be canceled.');">Delete Event</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No events have been created yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>

</body>
</html>