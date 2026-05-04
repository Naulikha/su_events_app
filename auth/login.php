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

                    // trying to add succes message
                    $_SESSION['flash_success'] = "Welcome back, " . $row['full_name'] . "!";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU Events - Login</title>

    <!-- BOOTSTRAP CSS (using link) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navbar dynamic -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/su_events_app/includes/navbar.php'; ?>
<!-- Inject Flash Notifications -->
    <?php include '../includes/flash.php'; ?>

<!-- BOOTSTRAP GRID SYSTEM -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-5"> <!-- Makes the card responsive and centered -->
                
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="text-center mb-4 fw-bold text-primary">Welcome Back</h3>
                        
                        <!-- Bootstrap Error Alert -->
                        <?php if(!empty($error)) echo "<div class='alert alert-danger py-2'>$error</div>"; ?>
                        
                        <form action="login.php" method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Email Address</label>
                                <input type="email" name="email" class="form-control form-control-lg" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label text-muted fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg" required>
                            </div>
                            
                            <button type="submit" name="login" class="btn btn-primary btn-lg w-100 fw-bold">Login</button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <span class="text-muted">Don't have an account?</span> 
                            <a href="register.php" class="text-decoration-none fw-bold">Sign up here</a>
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