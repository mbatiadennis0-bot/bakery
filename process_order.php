<?php
require_once 'auth.php';
include 'db.php';

require_active_shift($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
    $customer_name = trim($_POST['customer_name']);
    $staff_user = $_SESSION['username'];
    $group_id = "REC-" . strtoupper(substr(md5(uniqid('', true)), 0, 10));
    $cart_items = json_decode($_POST['cart_data'], true);

    if ($customer_name === '' || empty($cart_items)) {
        die("Error: Customer name and cart items are required.");
    }

    $conn->begin_transaction();

    try {
        foreach ($cart_items as $item) {
            if (!isset($item['id'], $item['qty'])) {
                throw new Exception("Invalid cart item detected.");
            }

            $product_id = (int) $item['id'];
            $qty = (int) $item['qty'];

            if ($qty < 1) {
                throw new Exception("Invalid quantity selected.");
            }

            $product_stmt = $conn->prepare("SELECT price, stock_level FROM products WHERE id = ? FOR UPDATE");
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            $product = $product_stmt->get_result()->fetch_assoc();
            $product_stmt->close();

            if (!$product) {
                throw new Exception("One of the selected products no longer exists.");
            }

            if ((int) $product['stock_level'] < $qty) {
                throw new Exception("Not enough stock for one of the selected products.");
            }

            $price = (float) $product['price'];
            $total_price = $qty * $price;

            $stmt = $conn->prepare("INSERT INTO orders (group_id, customer_name, product_id, quantity, total_price, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiids", $group_id, $customer_name, $product_id, $qty, $total_price, $staff_user);

            if (!$stmt->execute()) {
                throw new Exception("Order insertion failed: " . $stmt->error);
            }
            $stmt->close();

            $update = $conn->prepare("UPDATE products SET stock_level = stock_level - ? WHERE id = ?");
            $update->bind_param("ii", $qty, $product_id);
            if (!$update->execute()) {
                throw new Exception("Stock deduction execution failed: " . $update->error);
            }
            $update->close();
        }

        $conn->commit();
        header("Location: print_receipt.php?group_id=" . urlencode($group_id));
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Critical Transaction Error: " . $e->getMessage());
    }
}

header("Location: pos.php");
exit();
?>
