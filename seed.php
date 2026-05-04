<?php
// REFERENCE: Working with Databases (Lecture)
require_once 'config/db.php';

// Enable exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "<div style='font-family: Arial; padding: 20px;'>";
echo "<h2>🌱 System Data Seeder</h2>";

// ==========================================
// 1. GENERATE DUMMY USERS
// ==========================================
$default_password = password_hash('password123', PASSWORD_DEFAULT);

$users = [
    // Organisers
    ['Computing Society', 'COMP001', 'comp@su.edu', 'Organiser'],
    ['Drama Club', 'DRAMA01', 'drama@su.edu', 'Organiser'],
    ['Sports Union', 'SPORT01', 'sports@su.edu', 'Organiser'],
    // Attendees
    ['John Doe', 'STD10001', 'john@student.edu', 'Attendee'],
    ['Jane Smith', 'STD10002', 'jane@student.edu', 'Attendee']
];

echo "<h3>Seeding Users...</h3><ul>";

$organiser_ids = []; // We will save the Organiser IDs here to use for the events

$stmt = $conn->prepare("INSERT INTO users (full_name, student_id, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");

foreach ($users as $u) {
    try {
        $stmt->bind_param("sssss", $u[0], $u[1], $u[2], $default_password, $u[3]);
        $stmt->execute();
        $inserted_id = $stmt->insert_id;
        
        echo "<li style='color: green;'>Created {$u[3]}: {$u[0]}</li>";
        
        // Save the IDs of the organisers so we can assign events to them
        if ($u[3] === 'Organiser') {
            $organiser_ids[] = $inserted_id;
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo "<li style='color: orange;'>Skipped {$u[0]} (Already exists)</li>";
            // If they already exist, we need to fetch their ID to use for the events
            $fetch = $conn->query("SELECT user_id FROM users WHERE email = '{$u[2]}'");
            if ($row = $fetch->fetch_assoc()) {
                if ($u[3] === 'Organiser') $organiser_ids[] = $row['user_id'];
            }
        } else {
            echo "<li style='color: red;'>Error creating {$u[0]}: " . $e->getMessage() . "</li>";
        }
    }
}
$stmt->close();
echo "</ul>";

// ==========================================
// 2. GENERATE DUMMY EVENTS
// ==========================================
if (count($organiser_ids) >= 3) {
    $events = [
        [$organiser_ids[0], 'Computing Society', 'Annual Hackathon 2026', 'Join us for 24 hours of coding, pizza, and prizes!', date('Y-m-d H:i:s', strtotime('+5 days')), 'Library Tech Hub', 100, 'Academic', 'uploads/banner4.jpg'],
        [$organiser_ids[0], 'Computing Society', 'Cybersecurity Workshop', 'Learn the basics of ethical hacking.', date('Y-m-d H:i:s', strtotime('+12 days')), 'Room 402', 30, 'Academic', 'uploads/banner4.jpg'],
        [$organiser_ids[1], 'Drama Club', 'Improv Comedy Night', 'Laughs guaranteed.', date('Y-m-d H:i:s', strtotime('+3 days')), 'Main Auditorium', 150, 'Entertainment', 'uploads/banner2.jpg'],
        [$organiser_ids[1], 'Drama Club', 'Auditions: Hamlet', 'Open casting call.', date('Y-m-d H:i:s', strtotime('+10 days')), 'Studio B', 20, 'Social', 'uploads/banner1.jpg'],
        [$organiser_ids[2], 'Sports Union', 'Inter-varsity Basketball', 'Support our team!', date('Y-m-d H:i:s', strtotime('+2 days')), 'Campus Gymnasium', 200, 'Sports', 'uploads/banner5.jpg'],
        [$organiser_ids[2], 'Sports Union', 'Yoga for Beginners', 'De-stress before exams.', date('Y-m-d H:i:s', strtotime('+7 days')), 'Dance Studio', 25, 'Sports', 'uploads/banner3.jpg'],
    ];

    echo "<h3>Seeding Events...</h3><ul>";
    
    // UPDATE: We added image_path to the SQL and an extra "s" to the bind_param
    $stmt = $conn->prepare("INSERT INTO events (organiser_id, society_name, title, description, event_date, location, max_capacity, category, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($events as $e) {
        try {
            // We use INSERT IGNORE logic here via try-catch to prevent endless duplicates if run twice
            // (Assuming title is not unique, we just insert them. If you run this file 5 times, you get 30 events)
            $stmt->bind_param("isssssiss", $e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8]);
            $stmt->execute();
            echo "<li style='color: green;'>Created Event: {$e[2]} (with image)</li>";
        } catch (mysqli_sql_exception $ex) {
            echo "<li style='color: red;'>Error creating {$e[2]}: " . $ex->getMessage() . "</li>";
        }
    }
    $stmt->close();
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Could not seed events: Organiser IDs missing.</p>";
}

echo "<h3>✅ Database Successfully Seeded!</h3>";
echo "<p><strong>Test Accounts:</strong><br>Organiser: comp@su.edu (Password: password123)<br>Attendee: john@student.edu (Password: password123)</p>";
echo "<a href='index.php' style='display:inline-block; padding:10px 15px; background:#007bff; color:white; text-decoration:none; border-radius:4px;'>Go to Homepage</a>";
echo "</div>";
?>