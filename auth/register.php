<?php
// Start session to store user info later
// REFERENCE: Sessions (Lecture)
session_start();

// Include database connection
// REFERENCE: Working with Databases (Lecture)
require_once '../config/db.php'; 

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";

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
            
            try {
                // We TRY to execute the query
                $stmt->execute();
                
                // If it doesn't crash, it means success!
                // // NEW FLASH LOGIC
                $_SESSION['flash_success'] = "Registration successful! You can now login.";
                $stmt->close();

                // Send them to the login page
                header("Location: login.php");
                exit();
                
            } catch (mysqli_sql_exception $e) {
                // If it crashes, we CATCH the error in mid-air
                if ($e->getCode() == 1062) {
                    // 1062 is the exact code for "Duplicate Entry"
                    $error = "An account with this Email or Student ID already exists!";
                } else {
                    // This catches any other random database failures
                    $error = "Database error: " . $e->getMessage();
                }
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU Events - Register</title>
    <!-- BOOTSTRAP CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar dynamic -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/su_events_app/includes/navbar.php'; ?>
    <!-- for flash messages -->
    <?php include '../includes/flash.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="text-center mb-4 fw-bold text-primary">Register for SU Events</h3>
                        
                        <!-- Display PHP Errors in a Bootstrap Alert -->
                        <?php if(!empty($error)) echo "<div class='alert alert-danger py-2'>$error</div>"; ?>
                        
                        <!-- Deleted Account Message upgraded to Bootstrap Alert -->
                        <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'true'): ?>
                            <div class='alert alert-danger py-2'>Your account has been successfully deleted.</div>
                        <?php endif; ?>

                        <!-- Form with your novalidate logic! -->
                        <form action="register.php" method="POST" novalidate>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Full Name:</label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted fw-semibold">Student ID:</label>
                                    <input type="text" name="student_id" class="form-control" placeholder="6-20 characters" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted fw-semibold">I am a(n):</label>
                                    <select name="role" class="form-select" required>
                                        <option value="Attendee">Student (Attendee)</option>
                                        <option value="Organiser">Society Member (Organiser)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Email Address:</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted fw-semibold">Password:</label>
                                <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
                            </div>

                            <!-- I added name="register" here so the isset($_POST['register']) works perfectly -->
                            <button type="submit" name="register" class="btn btn-primary btn-lg w-100 fw-bold">Register</button>
                        </form>

                        <div class="text-center mt-4">
                            <span class="text-muted">Already have an account?</span> 
                            <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>