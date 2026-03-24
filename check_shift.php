<?php
// check_shift.php - Include this at the TOP of pages you want to lock during off-hours
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// If the user is Staff, they MUST be clocked in to proceed
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Staff') {
    $username = $_SESSION['username'];

    // Check the latest attendance record for this specific staff member
    $query = "SELECT status FROM attendance WHERE username = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // If no record found OR status is 'Out', redirect to attendance page
    if (!$row || $row['status'] !== 'In') {
        header("Location: attendance.php?error=must_clock_in");
        exit();
    }
}

// Admins (like dayknow) bypass this check automatically to manage the system 24/7
?>