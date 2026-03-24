<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// SECURITY: Only 'dayknow' can edit products
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'dayknow') {
    die("<div style='text-align:center; padding:50px; font-family:Poppins, sans-serif;'>
            <h2>🚫 Access Denied</h2>
            <p>Only Administrators can edit inventory items.</p>
            <a href='inventory.php'>Back to Inventory</a>
         </div>");
}

// 1. Get Product Details
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM products WHERE id = $id");
    $product = $result->fetch_assoc();
    
    if (!$product) { die("Product not found."); }
}

// 2. Handle the Update Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock_level'];

    $sql = "UPDATE products SET product_name=?, category=?, price=?, stock_level=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdii", $name, $category, $price, $stock, $id);
    
    if ($stmt->execute()) {
        header("Location: inventory.php?update=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product | Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #fdf2e9; font-family: 'Poppins', sans-serif; padding: 40px; }
        .edit-card { background: white; padding: 30px; border-radius: 20px; max-width: 500px; margin: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        .save-btn { background: #2a9d8f; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #e76f51; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="edit-card">
        <h2 style="color: #264653; margin-top: 0;">✏️ Edit Product</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $product['id'] ?>">

            <label>Product Name</label>
            <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
            
            <label>Category</label>
            <select name="category">
                <option value="Cakes" <?= $product['category'] == 'Cakes' ? 'selected' : '' ?>>Cakes</option>
                <option value="Bread" <?= $product['category'] == 'Bread' ? 'selected' : '' ?>>Bread</option>
                <option value="Pastries" <?= $product['category'] == 'Pastries' ? 'selected' : '' ?>>Pastries</option>
                <option value="Cookies" <?= $product['category'] == 'Cookies' ? 'selected' : '' ?>>Cookies</option>
            </select>

            <label>Price (Ksh)</label>
            <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>

            <label>Current Stock Level</label>
            <input type="number" name="stock_level" value="<?= $product['stock_level'] ?>" required>

            <button type="submit" class="save-btn">Update Product</button>
            <a href="inventory.php" class="cancel-link">Cancel Changes</a>
        </form>
    </div>
</body>
</html>