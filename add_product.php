<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY: Only 'dayknow' can access this page
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'dayknow') {
    die("<div style='text-align:center; padding:50px; font-family:Poppins, sans-serif;'>
            <h2>🚫 Access Denied</h2>
            <p>Only Administrators can add products.</p>
            <a href='inventory.php'>Return to Inventory</a>
         </div>");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock_level'];

    $sql = "INSERT INTO products (product_name, category, price, stock_level) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $name, $category, $price, $stock);
    
    if ($stmt->execute()) {
        header("Location: inventory.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product | Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #fdf2e9; font-family: 'Poppins', sans-serif; padding: 40px; }
        .form-card { background: white; padding: 30px; border-radius: 20px; max-width: 500px; margin: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        button { background: #2a9d8f; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2 style="color: #264653; margin-top: 0;">🍞 Add New Item</h2>
        <form method="POST">
            <label>Product Name</label>
            <input type="text" name="product_name" placeholder="e.g., Chocolate Muffin" required>
            
            <label>Category</label>
            <select name="category">
                <option value="Cakes">Cakes</option>
                <option value="Bread">Bread</option>
                <option value="Pastries">Pastries</option>
                <option value="Cookies">Cookies</option>
            </select>

            <label>Price (Ksh)</label>
            <input type="number" step="0.01" name="price" required>

            <label>Initial Stock Level</label>
            <input type="number" name="stock_level" value="0" required>

            <button type="submit">Add to Inventory</button>
            <a href="inventory.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none; font-size:14px;">Cancel</a>
        </form>
    </div>
</body>
</html>