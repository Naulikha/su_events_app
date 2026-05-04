<?php
// Start session to store user info later
// REFERENCE: Sessions (Lecture)
session_start();

// Include database connection
// REFERENCE: Working with Databases (Lecture)
require_once '../config/db.php'; 

$error = "";
$success = "";

// Check if form is submitted
// REFERENCE: PHP GET and POST (Lecture)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Sanitize Inputs to prevent malicious code
    // REFERENCE: PHP Form Validation & HTML Entities (Lectures)
    $fullName = htmlspecialchars(trim($_POST['full_name']));
    $studentId = htmlspecialchars(trim($_POST['student_id']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic Validation
   // Basic Validation & PATTERN MATCHING
    if (empty($fullName) || empty($studentId) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Prove server-side email validation
        $error = "Invalid email format.";
    } elseif (!preg_match("/^[a-zA-Z0-9]{6,20}$/", $studentId)) {
        // Prove server-side pattern matching: Student ID must be 6-20 letters/numbers
        $error = "Student ID must be between 6 and 20 alphanumeric characters.";
    } elseif (strlen($password) < 8) {
        // Prove server-side password strength validation
        $error = "Password must be at least 8 characters long.";
    } else {
        // 2. Hash the Password (Fits into our 100 char limit!)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 3. Insert into Database safely
        // REFERENCE: PHP_MYSQL_PREPARED_STATEMENTS (Lecture)
        // We use placeholders (?) to prevent SQL Injection
        $stmt = $conn->prepare("INSERT INTO users (full_name, student_id, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sssss", $fullName, $studentId, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // might header("Location: login.php"); here
            } else {
                $error = "Error: This Student ID or Email might already exist.";
            }
            $stmt->close();
        } else {
            $error = "Database error: Failed to prepare statement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SU Events - Register</title>
</head>
<body>
    <h2>Register for SU Events</h2>

    <!-- Display Messages -->
    <?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if(!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>

    <!-- Deleted Account: Catches the URL parameter from delete_account.php -->
    <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'true') echo "<p style='color:red;'>Your account has been successfully deleted.</p>"; ?>


    //added novalidate to allow php to take care of the validation
    <form action="register.php" method="POST" novalidate>
        <label>Full Name:</label><br>
        <input type="text" name="full_name" required><br><br>

        <label>Student ID:</label><br>
        <input type="text" name="student_id" required><br><br>

        <label>Email Address:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>I am a(n):</label><br>
        <select name="role" required>
            <option value="Attendee">Student (Attendee)</option>
            <option value="Organiser">Society Member (Organiser)</option>
        </select><br><br>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>