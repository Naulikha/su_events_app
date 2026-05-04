<?php
session_start();

// 🔐 MUST be logged in as Attendee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Attendee') {
    header("Location: ../auth/login.php");
    exit();
}

// 🔌 Database connection - FIXED DATABASE NAME
$conn = new mysqli("localhost", "root", "", "su_events");  

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// 🎟️ HANDLE BOOKING (from index.php)
if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];

    // prevent duplicate booking - FIXED COLUMN NAME
    $check = "SELECT * FROM bookings WHERE attendee_id='$user_id' AND event_id='$event_id'";  // ← CHANGED user_id to attendee_id
    $resultCheck = $conn->query($check);

    if ($resultCheck->num_rows == 0) {
        $sqlInsert = "INSERT INTO bookings (attendee_id, event_id) VALUES ('$user_id', '$event_id')";  // ← CHANGED user_id to attendee_id
        $conn->query($sqlInsert);
    }
}

// 📋 GET BOOKED EVENTS - FIXED JOIN COLUMN
$sql = "
SELECT events.title, events.event_date
FROM bookings
JOIN events ON bookings.event_id = events.event_id 
WHERE bookings.attendee_id = '$user_id' 
";

$result = $conn->query($sql);
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

    <h3>My Booked Events</h3>

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div>
                <strong><?php echo $row['title']; ?></strong><br>
                Date: <?php echo $row['event_date']; ?>
            </div>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No bookings yet.</p>
    <?php endif; ?>

    <br>

    <!-- Delete Account -->
    <form action="../auth/delete_account.php" method="POST"
        onsubmit="return confirm('Are you sure you want to delete your account?');">
        <button type="submit" style="color:red;">Delete My Account</button>
    </form>

    <br>
    <a href="../auth/logout.php">Logout</a>

</body>
</html>