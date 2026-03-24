<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Redirect to login if the user isn't logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. Handle Deleting a Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: inventory.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
   <style>
        .inventory-card {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=80') 
                        center/cover no-repeat;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2); /* Slightly deeper shadow */
            max-width: 1000px;
            margin: 20px auto;
            color: white; /* Makes all general text white */
        }

        /* The Glassmorphism Effect for the table area */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            background: rgba(255, 255, 255, 0.1); /* Light transparent overlay */
            backdrop-filter: blur(10px); /* Blurs the background image behind the table */
            border-radius: 10px;
            overflow: hidden;
        }

        th { 
            background: #264653; 
            color: white; 
            padding: 15px; 
            text-align: left; 
            font-size: 14px;
            text-transform: uppercase;
        }

        td { 
            padding: 15px; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.1); /* Subtle white lines */
            color: white; /* Ensures table data is readable */
        }

        /* Hover effect to help the eye track rows */
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .badge { 
            padding: 5px 10px; 
            border-radius: 5px; 
            font-size: 12px; 
            font-weight: bold; 
        }

        .btn-add { 
            background: #2a9d8f; 
            color: white; 
            padding: 10px 20px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: bold; 
            transition: 0.3s;
        }
        
        .btn-add:hover { background: #21867a; }
        
        /* Ensures strong tags aren't hidden by the dark background */
        td strong { color: #f4a261; } 
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="inventory-card">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: #264653; margin: 0;">Stock Inventory</h2>
            <a href="add_product.php" class="btn-add">+ Add New Product</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM products ORDER BY product_name ASC");
                while ($row = $result->fetch_assoc()):
                    $stock = $row['stock_level'] ?? 0;
                    $is_low = ($stock <= 5);
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['category'] ?? 'Bakery') ?></td>
                    <td>Ksh <?= number_format($row['price'], 2) ?></td>
                    <td><?= $stock ?> units</td>
                    <td>
                        <span class="badge" style="background: <?= $is_low ? '#e76f5120' : '#2a9d8f20' ?>; color: <?= $is_low ? '#e76f51' : '#2a9d8f' ?>;">
                            <?= $is_low ? 'Low Stock' : 'In Stock' ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_product.php?id=<?= $row['id'] ?>" style="text-decoration: none;">✏️</a>
                        <a href="inventory.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')" style="text-decoration: none; margin-left: 10px;">🗑️</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>