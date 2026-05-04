<?php
session_start();
require_once 'config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Check if an ID was passed in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_error'] = "Event not found.";
    header("Location: index.php");
    exit();
}

$event_id = (int)$_GET['id'];

// 2. Fetch Event Details and Organiser Name
$sql = "SELECT e.*, u.full_name AS organiser_name 
        FROM events e 
        JOIN users u ON e.organiser_id = u.user_id 
        WHERE e.event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_error'] = "This event does not exist or was deleted.";
    header("Location: index.php");
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// 3. Check Capacity / Sold Out Status
$cap_sql = "SELECT COUNT(*) as booked FROM bookings WHERE event_id = ?";
$cap_stmt = $conn->prepare($cap_sql);
$cap_stmt->bind_param("i", $event_id);
$cap_stmt->execute();
$current_bookings = $cap_stmt->get_result()->fetch_assoc()['booked'];
$cap_stmt->close();

$is_sold_out = ($current_bookings >= $event['max_capacity']);

// 4. If Attendee is logged in, check if they already booked it
$already_booked = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Attendee') {
    $check_sql = "SELECT booking_id FROM bookings WHERE attendee_id = ? AND event_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $_SESSION['user_id'], $event_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $already_booked = true;
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['title']); ?> - SU Events</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8f9fa; color: #333; line-height: 1.6; }
        
        header { background: #004aad; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #004aad; text-decoration: none; font-weight: bold; }
        .back-link:hover { text-decoration: underline; }
        
        .event-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 40px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* Left Column: Image & Description */
        .event-image { width: 100%; height: 350px; object-fit: cover; border-radius: 8px; margin-bottom: 20px; background: #e9ecef; }
        .event-title { font-size: 2.5rem; margin-bottom: 10px; color: #1a1a1a; line-height: 1.2; }
        .event-org { color: #666; font-size: 1.1rem; margin-bottom: 20px; }
        .category-badge { display: inline-block; background: #e9ecef; color: #004aad; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; margin-bottom: 20px; }
        
        /* Right Column: Ticket Card */
        .ticket-panel { background: #f8f9fa; padding: 25px; border-radius: 8px; border: 1px solid #eee; height: fit-content; position: sticky; top: 20px; }
        .panel-item { margin-bottom: 15px; }
        .panel-item strong { display: block; color: #555; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .panel-item span { font-size: 1.1rem; color: #111; font-weight: bold; }
        
        /* Buttons */
        .btn { display: block; width: 100%; text-align: center; padding: 15px; border-radius: 6px; font-size: 1.1rem; font-weight: bold; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; margin-top: 20px;}
        .btn-primary { background: #ff6b6b; color: white; }
        .btn-primary:hover { background: #e05c5c; }
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; }
        .btn-success { background: #28a745; color: white; pointer-events: none; }
        
        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>

    <?php include 'includes/flash.php'; ?>

    <header>
        <div style="font-size: 1.5rem; font-weight: bold;">🎓 SU Events</div>
        <nav>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php 
                    $dashboard = ($_SESSION['role'] === 'Admin') ? 'dashboard/admin.php' : 
                                (($_SESSION['role'] === 'Organiser') ? 'dashboard/organiser.php' : 'dashboard/attendee.php');
                ?>
                <a href="<?php echo $dashboard; ?>">My Dashboard</a>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <a href="index.php" class="back-link">← Back to Events</a>

        <div class="event-layout">
            <!-- Left Side: Main Content -->
            <div class="main-content">
                <?php if(!empty($event['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event Cover" class="event-image">
                <?php else: ?>
                    <div class="event-image" style="display:flex; align-items:center; justify-content:center; color:#999;">No Image Available</div>
                <?php endif; ?>

                <div class="category-badge"><?php echo htmlspecialchars($event['category']); ?></div>
                <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                <div class="event-org">Hosted by <strong><?php echo htmlspecialchars($event['society_name']); ?></strong> (<?php echo htmlspecialchars($event['organiser_name']); ?>)</div>
                
                <hr>
                
                <h3>About this event</h3>
                <p style="margin-top: 10px; white-space: pre-line; color: #444; font-size: 1.1rem;">
                    <?php echo htmlspecialchars($event['description']); ?>
                </p>
            </div>

            <!-- Right Side: Ticket Booking Panel -->
            <div class="ticket-panel">
                <div class="panel-item">
                    <strong>Date & Time</strong>
                    <span><?php echo date('l, F j, Y', strtotime($event['event_date'])); ?><br>
                          <?php echo date('g:i A', strtotime($event['event_date'])); ?></span>
                </div>
                
                <div class="panel-item">
                    <strong>Location</strong>
                    <span><?php echo htmlspecialchars($event['location']) ?: 'TBD'; ?></span>
                </div>

                <div class="panel-item">
                    <strong>Availability</strong>
                    <span>
                        <?php if ($is_sold_out): ?>
                            <span style="color: #dc3545;">Sold Out</span>
                        <?php else: ?>
                            <?php echo ($event['max_capacity'] - $current_bookings); ?> spots left!
                        <?php endif; ?>
                    </span>
                </div>

                <hr>

                <!-- THE SMART BUTTON LOGIC -->
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <!-- Not logged in -->
                    <a href="auth/login.php" class="btn btn-primary" style="background:#004aad;">Log in to Book</a>
                
                <?php elseif ($_SESSION['role'] !== 'Attendee'): ?>
                    <!-- Logged in as Admin or Organiser -->
                    <button class="btn btn-disabled">Attendees Only</button>
                
                <?php elseif ($already_booked): ?>
                    <!-- Attendee who already booked -->
                    <button class="btn btn-success">✓ Ticket Secured</button>
                    <div style="text-align:center; margin-top:10px;"><a href="dashboard/attendee.php" style="color:#004aad; font-size:0.9rem;">View your tickets</a></div>
                
                <?php elseif ($is_sold_out): ?>
                    <!-- Attendee, but event is full -->
                    <button class="btn btn-disabled">Sold Out</button>
                
                <?php else: ?>
                    <!-- Attendee ready to book! -->
                    <form method="POST" action="dashboard/attendee.php">
                        <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                        <button type="submit" class="btn btn-primary">Book Ticket Now</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>
</html>
