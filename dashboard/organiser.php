<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organiser') {
    header("Location: ../auth/login.php");
    exit();
}

include '../config/db.php';

// Server-side sanitization function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

//Image upload function
function uploadEventImage($file, $event_id) {
    $target_dir = "../uploads/";

    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($file['error'] == 0 && $file['size'] > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

        if (in_array($file['type'], $allowed_types)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = "event_" . $event_id . "_" . time() . "." . $extension;

            // Actual folder path for move_uploaded_file()
            $target_file = $target_dir . $new_filename;

            // Path saved in database for browser display
            $db_image_path = "uploads/" . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                return $db_image_path;
            }
        }
    }

    return null;
}

// Check if event is sold out
function isEventSoldOut($conn, $event_id) {
    $count_sql = "SELECT COUNT(*) as booked FROM bookings WHERE event_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $event_id);
    $count_stmt->execute();
    $booked = $count_stmt->get_result()->fetch_assoc()['booked'];

    $cap_sql = "SELECT max_capacity FROM events WHERE event_id = ?";
    $cap_stmt = $conn->prepare($cap_sql);
    $cap_stmt->bind_param("i", $event_id);
    $cap_stmt->execute();
    $capacity = $cap_stmt->get_result()->fetch_assoc()['max_capacity'];

    return $booked >= $capacity;
}

// Handle create event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $event_date = sanitizeInput($_POST['event_date']);
    $location = sanitizeInput($_POST['location']);
    $max_capacity = sanitizeInput($_POST['max_capacity']);
    $society_name = sanitizeInput($_POST['society_name']);
    $category = sanitizeInput($_POST['category']);
    $organiser_id = $_SESSION['user_id'];

    $errors = [];

    if (empty($title)) $errors[] = "Event title is required";
    if (empty($event_date)) $errors[] = "Event date is required";
    if (empty($society_name)) $errors[] = "Society name is required";
    if (!is_numeric($max_capacity) || $max_capacity <= 0) $errors[] = "Max capacity must be a positive number";

    if (empty($errors)) {
        $sql = "INSERT INTO events 
                (organiser_id, society_name, title, description, event_date, location, max_capacity, category) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssis",
            $organiser_id,
            $society_name,
            $title,
            $description,
            $event_date,
            $location,
            $max_capacity,
            $category
        );

        if ($stmt->execute()) {
            $event_id = $stmt->insert_id;

            if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0 && $_FILES['event_image']['size'] > 0) {
                $image_path = uploadEventImage($_FILES['event_image'], $event_id);

                if ($image_path) {
                    $img_sql = "UPDATE events SET image_path = ? WHERE event_id = ?";
                    $img_stmt = $conn->prepare($img_sql);
                    $img_stmt->bind_param("si", $image_path, $event_id);
                    $img_stmt->execute();
                    $img_stmt->close();

                    $success = "Event created with image!";
                } else {
                    $success = "Event created, but image upload failed.";
                }
            } else {
                $success = "Event created successfully!";
            }
        } else {
            $error = "Something went wrong: " . $conn->error;
        }

        $stmt->close();
    } else {
        $error = implode(", ", $errors);
    }
}

// Handle delete event
if (isset($_GET['delete'])) {
    $event_id = sanitizeInput($_GET['delete']);

    $sql = "DELETE FROM events WHERE event_id = ? AND organiser_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $event_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        $success = "Event deleted successfully!";
    } else {
        $error = "Could not delete event";
    }

    $stmt->close();
}

// Handle update event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_event'])) {
    $event_id = sanitizeInput($_POST['event_id']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $event_date = sanitizeInput($_POST['event_date']);
    $location = sanitizeInput($_POST['location']);
    $max_capacity = sanitizeInput($_POST['max_capacity']);
    $society_name = sanitizeInput($_POST['society_name']);
    $category = sanitizeInput($_POST['category']);

    $sql = "UPDATE events 
            SET title = ?, description = ?, event_date = ?, location = ?, max_capacity = ?, society_name = ?, category = ?
            WHERE event_id = ? AND organiser_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssissii",
        $title,
        $description,
        $event_date,
        $location,
        $max_capacity,
        $society_name,
        $category,
        $event_id,
        $_SESSION['user_id']
    );

    if ($stmt->execute()) {
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0 && $_FILES['event_image']['size'] > 0) {
            $image_path = uploadEventImage($_FILES['event_image'], $event_id);

            if ($image_path) {
                $img_sql = "UPDATE events SET image_path = ? WHERE event_id = ?";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("si", $image_path, $event_id);
                $img_stmt->execute();
                $img_stmt->close();
            }
        }

        $success = "Event updated successfully!";
    } else {
        $error = "Error updating event: " . $conn->error;
    }

    $stmt->close();
}

// Get all events for this organiser
$sql = "SELECT * FROM events WHERE organiser_id = ? ORDER BY event_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Check if viewing attendee roster
$show_roster = false;
$roster_event = null;
$roster_attendees = null;

if (isset($_GET['view_roster'])) {
    $roster_event_id = sanitizeInput($_GET['view_roster']);

    $event_sql = "SELECT * FROM events WHERE event_id = ? AND organiser_id = ?";
    $event_stmt = $conn->prepare($event_sql);
    $event_stmt->bind_param("ii", $roster_event_id, $_SESSION['user_id']);
    $event_stmt->execute();
    $roster_event = $event_stmt->get_result()->fetch_assoc();

    if ($roster_event) {
        $show_roster = true;

        $roster_sql = "SELECT b.booking_id, b.booking_date, u.full_name, u.email, u.student_id
                       FROM bookings b
                       JOIN users u ON b.attendee_id = u.user_id
                       WHERE b.event_id = ?
                       ORDER BY b.booking_date DESC";

        $roster_stmt = $conn->prepare($roster_sql);
        $roster_stmt->bind_param("i", $roster_event_id);
        $roster_stmt->execute();
        $roster_attendees = $roster_stmt->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organiser Dashboard - Manage Events</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f9; padding: 20px; }
        .container { max-width: 1300px; margin: 0 auto; }
        h1, h2 { color: #333; margin-bottom: 20px; }
        .form-section { background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input[type="file"] { padding: 5px; }
        textarea { resize: vertical; min-height: 80px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 20px; }
        .event-card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .event-card:hover { transform: translateY(-3px); }
        .event-card h3 { color: #007bff; margin-bottom: 10px; }
        .event-detail { margin: 10px 0; color: #666; }
        .event-image { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 10px; background: #e9ecef; }
        .category-badge { display: inline-block; background: #e9ecef; padding: 3px 8px; border-radius: 12px; font-size: 12px; margin-bottom: 10px; }
        .sold-out { background: #dc3545; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px; }
        .btn-update { background: #ffc107; color: #333; margin-right: 10px; }
        .btn-update:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; }
        .btn-delete:hover { background: #c82333; }
        .btn-roster { background: #17a2b8; }
        .btn-roster:hover { background: #138496; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .edit-form { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .btn-small { padding: 6px 12px; font-size: 14px; }
        .actions { margin-top: 15px; }
        .roster-table { width: 100%; border-collapse: collapse; background: white; margin-top: 15px; }
        .roster-table th, .roster-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .roster-table th { background: #007bff; color: white; }
        .logout-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
        hr { margin: 20px 0; border: none; border-top: 2px solid #eee; }
        .delete-account-form { display: inline; }
    </style>
</head>

<body>
    <!-- Navbar dynamic -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/su_events_app/includes/navbar.php'; ?>
    
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p style="color: #666; margin-bottom: 30px;">Manage all your society events from this dashboard.</p>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($show_roster && $roster_event): ?>

            <!-- ATTENDEE ROSTER VIEW -->
            <div class="form-section">
                <button onclick="window.location.href='organiser.php'" class="btn-small" style="background:#6c757d; margin-bottom:15px;">← Back to Events</button>

                <h2>Attendee Roster: <?php echo htmlspecialchars($roster_event['title']); ?></h2>

                <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($roster_event['event_date'])); ?></p>
                <p><strong>Bookings:</strong> <?php echo $roster_attendees->num_rows; ?> / <?php echo $roster_event['max_capacity']; ?></p>

                <?php if ($roster_attendees->num_rows > 0): ?>
                    <table class="roster-table">
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Booked Date</th>
                        </tr>

                        <?php while ($attendee = $roster_attendees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attendee['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($attendee['booking_date'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p style="color: #999;">No attendees have booked this event yet.</p>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <!-- CREATE EVENT SECTION -->
            <div class="form-section">
                <h2>Create New Event</h2>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Society Name *</label>
                        <input type="text" name="society_name" required placeholder="e.g., Computing Society">
                    </div>

                    <div class="form-group">
                        <label>Event Title *</label>
                        <input type="text" name="title" required>
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Academic">Academic</option>
                            <option value="Social">Social</option>
                            <option value="Sports">Sports</option>
                            <option value="Career">Career</option>
                            <option value="Entertainment">Entertainment</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Describe your event..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Date and Time *</label>
                        <input type="datetime-local" name="event_date" required>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Where will this event take place?">
                    </div>

                    <div class="form-group">
                        <label>Maximum Capacity</label>
                        <input type="number" name="max_capacity" value="50">
                    </div>

                    <div class="form-group">
                        <label>Event Banner Image</label>
                        <input type="file" name="event_image" accept="image/jpeg,image/png,image/jpg">
                        <small style="color: #666;">Upload a JPG or PNG image optional</small>
                    </div>

                    <button type="submit" name="create_event">Create Event</button>
                </form>
            </div>

            <!-- DISPLAY EVENTS SECTION -->
            <h2>Your Events</h2>

            <?php if ($result->num_rows == 0): ?>
                <p style="color: #999;">You haven't created any events yet. Use the form above to get started.</p>
            <?php else: ?>
                <div class="event-grid">
                    <?php while ($event = $result->fetch_assoc()): ?>
                        <div class="event-card">

                            <?php if (!empty($event['image_path'])): ?>
                                <img src="/su_events_app/<?php echo htmlspecialchars($event['image_path']); ?>" class="event-image" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <?php else: ?>
                                <div class="event-image" style="display:flex; align-items:center; justify-content:center; color:#999;">No Image</div>
                            <?php endif; ?>

                            <div class="category-badge"><?php echo htmlspecialchars($event['category']); ?></div>

                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>

                            <p><strong>Society:</strong> <?php echo htmlspecialchars($event['society_name']); ?></p>

                            <p><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 100))); ?>...</p>

                            <div class="event-detail">
                                <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?>
                            </div>

                            <div class="event-detail">
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']) ?: 'Not specified'; ?>
                            </div>

                            <div class="event-detail">
                                <strong>Capacity:</strong> <?php echo $event['max_capacity']; ?> people

                                <?php if (isEventSoldOut($conn, $event['event_id'])): ?>
                                    <span class="sold-out">SOLD OUT</span>
                                <?php endif; ?>
                            </div>

                            <div class="actions">
                                <button onclick="toggleEdit(<?php echo $event['event_id']; ?>)" class="btn-update btn-small">Update</button>

                                <a href="?delete=<?php echo $event['event_id']; ?>" onclick="return confirm('Are you sure you want to delete this event?')">
                                    <button type="button" class="btn-delete btn-small">Delete</button>
                                </a>

                                <a href="?view_roster=<?php echo $event['event_id']; ?>">
                                    <button type="button" class="btn-roster btn-small">View Attendees</button>
                                </a>
                            </div>

                            <!-- Hidden update form -->
                            <div id="edit-<?php echo $event['event_id']; ?>" style="display:none;" class="edit-form">
                                <h4 style="margin-bottom: 10px;">Edit Event</h4>

                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">

                                    <div class="form-group">
                                        <label>Society Name</label>
                                        <input type="text" name="society_name" value="<?php echo htmlspecialchars($event['society_name']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category" required>
                                            <option value="Academic" <?php echo $event['category'] == 'Academic' ? 'selected' : ''; ?>>Academic</option>
                                            <option value="Social" <?php echo $event['category'] == 'Social' ? 'selected' : ''; ?>>Social</option>
                                            <option value="Sports" <?php echo $event['category'] == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                                            <option value="Career" <?php echo $event['category'] == 'Career' ? 'selected' : ''; ?>>Career</option>
                                            <option value="Entertainment" <?php echo $event['category'] == 'Entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" rows="2"><?php echo htmlspecialchars($event['description']); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Date and Time</label>
                                        <input type="datetime-local" name="event_date" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date'])); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="location" value="<?php echo htmlspecialchars($event['location']); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Max Capacity</label>
                                        <input type="number" name="max_capacity" value="<?php echo $event['max_capacity']; ?>">
                                    </div>

                                    <div class="form-group">
                                        <label>Update Banner Image</label>
                                        <input type="file" name="event_image" accept="image/jpeg,image/png,image/jpg">

                                        <?php if (!empty($event['image_path'])): ?>
                                            <small>Current image uploaded</small>
                                        <?php endif; ?>
                                    </div>

                                    <button type="submit" name="update_event" class="btn-small">Save Changes</button>
                                    <button type="button" onclick="toggleEdit(<?php echo $event['event_id']; ?>)" class="btn-small" style="background:#6c757d;">Cancel</button>
                                </form>
                            </div>

                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <hr>

            <a href="../auth/logout.php" class="logout-link">Logout</a>

            &nbsp;|&nbsp;

            <form action="../auth/delete_account.php" method="POST" class="delete-account-form" onsubmit="return confirm('Warning: This will delete ALL your events and data. Are you absolutely sure?');">
                <button type="submit" style="color:#dc3545; background:none; border:none; padding:0; font:inherit; cursor:pointer; text-decoration:underline;">Delete My Account</button>
            </form>

        <?php endif; ?>
    </div>

    <script>
        function toggleEdit(eventId) {
            var editDiv = document.getElementById('edit-' + eventId);

            if (editDiv.style.display === 'none' || editDiv.style.display === '') {
                editDiv.style.display = 'block';
            } else {
                editDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
