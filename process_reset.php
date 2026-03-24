<?php
session_start();
include "db.php";

// 1. SECURITY: Ensure only logged-in users can change passwords
// Or check if the person is an Admin resetting a staff member's pass
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 2. VALIDATION: Check if passwords match
    if ($new_pass !== $confirm_pass) {
        echo "<script>alert('Error: Passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // 3. VALIDATION: Check password strength (at least 6 characters)
    if (strlen($new_pass) < 6) {
        echo "<script>alert('Error: Password must be at least 6 characters!'); window.history.back();</script>";
        exit();
    }

    // Secure the new password
    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

    // 4. EXECUTION: Update the password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed_password, $user);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Success! Password updated.'); window.location='dashboard.php';</script>";
        } else {
            echo "<script>alert('Error: Username not found or same as old password.'); window.history.back();</script>";
        }
    } else {
        echo "System Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: change_password.php");
    exit();
}
?>