<?php
// REFERENCE: Sessions (Lecture)
session_start();

// 🔐 THE BOUNCER: MUST be logged in as Attendee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Attendee') {
    header("Location: ../auth/login.php");
    exit();
}

// 🔌 SECURE Database connection
require_once '../config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = $_SESSION['user_id'];

// ==========================================
// 🎟️ TICKET BOOKING ENGINE (Secured)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['event_id'])) {
    $event_id = (int)$_POST['event_id']; // Typecast to integer to immediately block SQL injection text

    try {
        // Step 1: Check the Event Capacity AND current bookings
        $cap_sql = "SELECT e.max_capacity, (SELECT COUNT(*) FROM bookings WHERE event_id = ?) AS current_bookings 
                    FROM events e WHERE e.event_id = ?";
        $cap_stmt = $conn->prepare($cap_sql);
        $cap_stmt->bind_param("ii", $event_id, $event_id);
        $cap_stmt->execute();
        $cap_result = $cap_stmt->get_result();
        
        if ($row = $cap_result->fetch_assoc()) {
            if ($row['current_bookings'] >= $row['max_capacity']) {
                $_SESSION['flash_error'] = "Sorry! This event is sold out.";
            } else {
                // Step 2: Check if the user already booked it
                $check_stmt = $conn->prepare("SELECT booking_id FROM bookings WHERE attendee_id = ? AND event_id = ?");
                $check_stmt->bind_param("ii", $user_id, $event_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $_SESSION['flash_error'] = "You already have a ticket for this event!";
                } else {
                    // Step 3: Securely insert the booking!
                    $insert_stmt = $conn->prepare("INSERT INTO bookings (attendee_id, event_id) VALUES (?, ?)");
                    $insert_stmt->bind_param("ii", $user_id, $event_id);
                    $insert_stmt->execute();
                    $_SESSION['flash_success'] = "Ticket booked successfully! See you there.";
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        } else {
            $_SESSION['flash_error'] = "Event not found.";
        }
        $cap_stmt->close();

        // PRG Pattern: Redirect to clear the form!
        header("Location: attendee.php");
        exit();

    } catch (mysqli_sql_exception $e) {
        $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
        header("Location: attendee.php");
        exit();
    }
}

// ==========================================
// 📋 GET BOOKED EVENTS (Secured JOIN)
// ==========================================
$sql = "SELECT e.title, e.event_date, e.location, e.image_path, b.booking_date 
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id 
        WHERE b.attendee_id = ?
        ORDER BY e.event_date ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - My Tickets</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f9; }
        .ticket-card { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ticket-card h4 { margin: 0 0 10px 0; color: #333; }
        .ticket-detail { color: #666; font-size: 14px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <!-- Inject your beautiful Flash Notifications! -->
    <?php include '../includes/flash.php'; ?>

    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>! 🎓</h2>
    <p>Here is your digital wallet of booked events.</p>
    
    <a href="../index.php" style="background:#28a745; color:white; padding:8px 15px; text-decoration:none; border-radius:4px;">Browse More Events</a>
    <a href="../auth/logout.php" style="margin-left:10px;">Logout</a>
    <hr>

    <h3>My Digital Tickets</h3>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="ticket-card">
                <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                <div class="ticket-detail">📅 <strong>Date:</strong> <?php echo date('F j, Y @ g:i A', strtotime($row['event_date'])); ?></div>
                <div class="ticket-detail">📍 <strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></div>
                <div class="ticket-detail" style="font-size: 12px; color: #999; margin-top: 10px;">Ticket secured on: <?php echo date('M d, Y', strtotime($row['booking_date'])); ?></div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: #666;">You haven't booked any events yet. Go browse the homepage!</p>
    <?php endif; ?>

    <hr style="margin-top: 40px;">
    
    <form action="../auth/delete_account.php" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? All your tickets will be lost!');">
        <button type="submit" style="color:red; background:none; border:none; text-decoration:underline; cursor:pointer;">Delete My Account</button>
    </form>

</body>
</html>
