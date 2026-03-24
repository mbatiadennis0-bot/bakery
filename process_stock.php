<?php
session_start();
include "db.php"; 

// 1. SECURITY: Only logged-in users (or specifically Admins) should add stock
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and Format Input
    $name  = trim($_POST['product_name']);
    $cat   = $_POST['category'];
    $price = floatval($_POST['price']);
    $qty   = intval($_POST['stock_quantity']);

    // 2. VALIDATION: Ensure no empty values
    if (empty($name) || $price <= 0 || $qty < 0) {
        echo "<script>alert('Error: Please provide a valid name, price, and quantity.'); window.history.back();</script>";
        exit();
    }

    // 3. DUPLICATE CHECK: Prevent adding the same bread/cake twice
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE product_name = ?");
    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Error: This product already exists! Use the Edit button on the dashboard to add more stock.'); window.history.back();</script>";
    } else {
        // 4. INSERT: Add the new bakery item
        $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, stock_quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $name, $cat, $price, $qty);

        if ($stmt->execute()) {
            echo "<script>alert('Success! $name added to inventory.'); window.location='dashboard.php';</script>";
        } else {
            echo "System Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    $check_stmt->close();
    $conn->close();
} else {
    header("Location: dashboard.php");
    exit();
}
?>