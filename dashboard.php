<?php
include 'db.php';
include 'navbar.php';

// Correct Query: Uses 'stock_level'
$query = "SELECT * FROM products ORDER BY product_name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - Bakery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdfaf7; padding: 20px; }
        .main-content { max-width: 1200px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); }
        .page-title { color: #264653; border-left: 5px solid #f4a261; padding-left: 15px; margin-bottom: 30px; font-size: 24px; }
        .inventory-table { width: 100%; border-collapse: collapse; }
        .inventory-table th { background: #264653; color: white; padding: 15px; text-align: left; }
        .inventory-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; color: #444; }
        .status-low { color: #e76f51; font-weight: 600; }
        .status-ok { color: #2a9d8f; font-weight: 600; }
    </style>
</head>
<body>
    <div class="main-content">
        <h2 class="page-title">Current Inventory</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price (Ksh)</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= number_format($row['price'], 2) ?></td>
                    <td><?= $row['stock_level'] ?></td>
                    <td>
                        <?php if($row['stock_level'] <= 5): ?>
                            <span class="status-low">Low Stock</span>
                        <?php else: ?>
                            <span class="status-ok">Available</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>