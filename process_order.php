<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) { 
    header("Location: login.php"); 
    exit(); 
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $staff_user = $_SESSION['username'];
    $group_id = "REC-" . strtoupper(substr(md5(uniqid()), 0, 10)); 
    $cart_items = json_decode($_POST['cart_data'], true);

    if (empty($cart_items)) {
        die("Error: Your cart is empty.");
    }

    $conn->begin_transaction();

    try {
        foreach ($cart_items as $item) {
            // Check for Product ID from POS
            if (!isset($item['id'])) {
                throw new Exception("Product ID is missing for one of the items.");
            }

            $product_id = intval($item['id']);
            $qty = intval($item['qty']);
            $price = floatval($item['price']);
            $total_price = $qty * $price;

            // 1. INSERT INTO ORDERS
            // Ensure column names match your DESCRIBE orders result
            $stmt = $conn->prepare("INSERT INTO orders (group_id, customer_name, product_id, quantity, total_price, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiids", $group_id, $customer_name, $product_id, $qty, $total_price, $staff_user);
            
            if (!$stmt->execute()) {
                throw new Exception("Order insertion failed: " . $stmt->error);
            }

            // 2. DEDUCT STOCK
            // Changed 'quantity' to 'stock_level' to match your DB column
            $update = $conn->prepare("UPDATE products SET stock_level = stock_level - ? WHERE id = ?"); 
            if (!$update) {
                throw new Exception("Stock update prepare failed: " . $conn->error);
            }
            
            $update->bind_param("ii", $qty, $product_id);
            if (!$update->execute()) {
                throw new Exception("Stock deduction execution failed: " . $update->error);
            }
        }

        // Only commit if ALL steps above succeeded
        $conn->commit();
        header("Location: print_receipt.php?group_id=" . urlencode($group_id));
        exit();

    } catch (Exception $e) {
        // Rollback ensures no partial or "ghost" orders are saved if an error occurs
        $conn->rollback();
        die("Critical Transaction Error: " . $e->getMessage());
    }
} else {
    header("Location: pos.php");
    exit();
}
?>