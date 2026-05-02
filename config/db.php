<?php
/* 
 * Database Connection File
 * REFERENCE: Working with Databases (Lecture)
 */

$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password is blank
$dbname = "su_events";

// Establish the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Connection successful visual tester 
// echo "Connected successfully to su_events!";
?>