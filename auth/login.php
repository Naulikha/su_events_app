<?php
// Start the session IMMEDIATELY so we can store login state
// REFERENCE: Sessions (Lecture)
session_start();

// Include database connection
// REFERENCE: Working with Databases (Lecture)
require_once '../config/db.php';

$error = "";

// Check if form is submitted
// REFERENCE: PHP GET and POST (Lecture)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize the email just in case
    // REFERENCE: PHP Form Validation (Lecture)
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Find the user by their email using a Prepared Statement
        // REFERENCE: PHP_MYSQL_PREPARED_STATEMENTS (Lecture)
        $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Did we find a user with that email?
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // CRITICAL STEP: Verify the typed password against the 100-char hashed password in the DB
                if (password_verify($password, $user['password_hash'])) {
                    
                    // PASSWORDS MATCH! Set up the Session Variables
                    // REFERENCE: Sessions (Lecture)
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];

                    // The Traffic Cop: Redirect them based on their specific role
                    if ($user['role'] === 'Admin') {
                        header("Location: ../dashboard/admin.php");
                    } elseif ($user['role'] === 'Organiser') {
                        header("Location: ../dashboard/organiser.php");
                    } else {
                        // Regular Student/Attendee goes to the main events page  or attendee page 
                        // header("Location: ../index.php"); 
                        header("Location: ../dashboard/attendee.php"); 
                    }
                    exit(); // Always use exit() after a header redirect!

                } else {
                    $error = "Invalid password.";
                }
            } else {
                $error = "No account found with that email.";
            }
            $stmt->close();
        } else {
            $error = "Database error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SU Events - Login</title>
</head>
<body>
<!-- Inject Flash Notifications -->
    <?php include '../includes/flash.php'; ?>

    <h2>Login to SU Events</h2>

    <!-- Display Error Messages -->
    <?php if(!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    //added novalidate to allow php to do the validation
    <form action="login.php" method="POST" novalidate>
        <label>Email Address:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
    </form>
    
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>