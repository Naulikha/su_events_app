<?php
// Start session to check if user is logged in
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// If not logged in OR not an organiser, don't let them in 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Organiser') {
    header("Location: ../auth/login.php");
    exit();
}

// Connect to database
include '../config/db.php';

// Handle create event form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $max_capacity = $_POST['max_capacity'];
    $society_name = $_POST['society_name'];
    $organiser_id = $_SESSION['user_id'];

    $sql = "INSERT INTO events (organiser_id, society_name, title, description, event_date, location, max_capacity) 
            VALUES ('$organiser_id', '$society_name', '$title', '$description', '$event_date', '$location', '$max_capacity')";
    
    if ($conn->query($sql) === TRUE) {
        $success = "Event created successfully!";
    } else {
        $error = "Something went wrong: " . $conn->error;
    }
}

// Handle delete event
if (isset($_GET['delete'])) {
    $event_id = $_GET['delete'];
    $sql = "DELETE FROM events WHERE event_id = '$event_id' AND organiser_id = '" . $_SESSION['user_id'] . "'";
    if ($conn->query($sql) === TRUE) {
        $success = "Event deleted successfully!";
    } else {
        $error = "Could not delete event";
    }
}

// Handle update event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_event'])) {
    $event_id = $_POST['event_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $max_capacity = $_POST['max_capacity'];
    $society_name = $_POST['society_name'];

    $sql = "UPDATE events SET title='$title', description='$description', event_date='$event_date', 
            location='$location', max_capacity='$max_capacity', society_name='$society_name'
            WHERE event_id='$event_id' AND organiser_id='" . $_SESSION['user_id'] . "'";
    
    if ($conn->query($sql) === TRUE) {
        $success = "Event updated successfully!";
    } else {
        $error = "Error updating event";
    }
}

// Get all events created by this organiser
$sql = "SELECT * FROM events WHERE organiser_id = '" . $_SESSION['user_id'] . "' ORDER BY event_date ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organiser Dashboard - Manage Events</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f9;
            padding: 20px;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #0056b3;
        }
        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .event-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .event-card h3 {
            color: #007bff;
            margin-bottom: 10px;
        }
        .event-detail {
            margin: 10px 0;
            color: #666;
        }
        .btn-update {
            background: #ffc107;
            color: #333;
            margin-right: 10px;
        }
        .btn-update:hover {
            background: #e0a800;
        }
        .btn-delete {
            background: #dc3545;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .edit-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .btn-small {
            padding: 6px 12px;
            font-size: 14px;
        }
        .actions {
            margin-top: 15px;
        }
        .logout-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .logout-link:hover {
            text-decoration: underline;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 2px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p style="color: #666; margin-bottom: 30px;">Manage all your society events from this dashboard.</p>
        
        <!-- Show success/error messages if any -->
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- CREATE EVENT SECTION -->
        <div class="form-section">
            <h2>Create New Event</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Society Name *</label>
                    <input type="text" name="society_name" required placeholder="e.g., Computing Society">
                </div>
                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" required>
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
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p><strong>Society:</strong> <?php echo htmlspecialchars($event['society_name']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        <div class="event-detail">
                            <strong>Date:</strong> <?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?>
                        </div>
                        <div class="event-detail">
                            <strong>Location:</strong> <?php echo htmlspecialchars($event['location']) ?: 'Not specified'; ?>
                        </div>
                        <div class="event-detail">
                            <strong>Capacity:</strong> <?php echo $event['max_capacity']; ?> people
                        </div>
                        
                        <div class="actions">
                            <button onclick="toggleEdit(<?php echo $event['event_id']; ?>)" class="btn-update btn-small">Update</button>
                            <a href="?delete=<?php echo $event['event_id']; ?>" onclick="return confirm('Are you sure you want to delete this event? This cannot be undone.')">
                                <button type="button" class="btn-delete btn-small">Delete</button>
                            </a>
                        </div>

                        <!-- Hidden update form that appears when Update button is clicked -->
                        <div id="edit-<?php echo $event['event_id']; ?>" style="display:none;" class="edit-form">
                            <h4 style="margin-bottom: 10px;">Edit Event</h4>
                            <form method="POST">
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
        <a href="../auth/delete_account.php" class="logout-link" onclick="return confirm('Warning: This will delete ALL your events and data. Are you absolutely sure?')" style="color:#dc3545;">Delete My Account</a>
    </div>

    <script>
        // Simple function to show/hide the edit form
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