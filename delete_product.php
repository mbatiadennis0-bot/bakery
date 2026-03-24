<?php
// 1. Start session and check for Admin permissions
session_start();
include 'db.php'; 

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access. Only Admins can delete products.");
}

// 2. Check if an 'id' was sent in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare the SQL command to delete the specific item
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // 3. Success: Redirect back to the dashboard with a success message
        header("Location: dashboard.php?msg=Product deleted successfully");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
} else {
    // Redirect back if no valid ID is provided
    header("Location: dashboard.php?error=Invalid product ID");
    exit();
}

$conn->close();
?>