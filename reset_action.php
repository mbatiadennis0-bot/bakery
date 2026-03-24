<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize and Get Input
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];

    // 2. Hash the new password securely
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    // 3. Use a Prepared Statement (Prevents SQL Injection)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $username);

    if ($stmt->execute()) {
        // 4. Check if a row was actually updated
        if ($stmt->affected_rows > 0) {
            header("Location: login.php?success=" . urlencode("Password updated successfully"));
        } else {
            // Username doesn't exist or password is the same as the old one
            header("Location: login.php?error=" . urlencode("Username not found or no changes made"));
        }
    } else {
        header("Location: login.php?error=" . urlencode("System error. Please try again."));
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: login.php");
    exit();
}
?>