<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // 1. Use a Prepared Statement for security
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 2. Verify hashed password
        if (password_verify($pass, $row['password'])) {
            // 3. Store EVERYTHING needed for the system to work
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // CRITICAL for Admin/Staff checks
            $_SESSION['user_id'] = $row['id'];
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password. Please try again.'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Username not recognized.'); window.location='login.php';</script>";
    }
    $stmt->close();
}
?>