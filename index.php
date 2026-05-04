<?php
$conn = new mysqli("localhost", "root", "", "su_events");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM events WHERE event_date >= CURDATE()";
$result = $conn->query($sql);
?>

<h2>Upcoming Events</h2>

<?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div>";
        echo "<h3>" . $row['title'] . "</h3>";
        echo "<p>" . $row['description'] . "</p>";
        echo "<p>Date: " . $row['event_date'] . "</p>";

        echo "<form method='POST' action='dashboard/attendee.php'>";
        echo "<input type='hidden' name='event_id' value='" . $row['event_id'] . "'>";
        echo "<button type='submit'>Book Ticket</button>";
        echo "</form>";

        echo "</div><hr>";
    }
} else {
    echo "No events found.";
}
?>