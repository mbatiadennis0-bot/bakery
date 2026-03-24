<?php
include 'db.php';
// navbar.php already handles session_start() and security redirects
include 'navbar.php';

// Joining tables to show the actual product name instead of just an ID
// Note: We use 'processed_by' here to match your orders table schema
$query = "SELECT orders.*, products.product_name 
          FROM orders 
          JOIN products ON orders.product_id = products.id 
          ORDER BY orders.order_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Orders - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdfaf7; padding: 20px; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-accent { color: #264653; border-left: 5px solid #f4a261; padding-left: 15px; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #264653; color: white; padding: 12px; text-align: left; font-size: 13px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .staff-tag { background: #e9f0fe; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        
        .btn-receipt { 
            background: #f4a261; 
            color: white; 
            text-decoration: none; 
            padding: 8px 15px; 
            border-radius: 8px; 
            font-size: 12px; 
            font-weight: 600;
            display: inline-block;
            transition: 0.3s;
        }
        .btn-receipt:hover { background: #e76f51; box-shadow: 0 2px 8px rgba(231, 111, 81, 0.3); }

        .search-box { padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 250px; font-family: inherit; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h2 class="header-accent">All Staff Transactions</h2>
            <input type="text" id="orderSearch" class="search-box" placeholder="Search customer or staff..." onkeyup="filterTable()">
        </div>

        <table id="ordersTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Total (Ksh)</th>
                    <th>Processed By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, H:i', strtotime($row['order_date'])) ?></td>
                    <td><strong><?= htmlspecialchars($row['customer_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['product_name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td style="font-weight: 600; color: #264653;"><?= number_format($row['total_price'], 2) ?></td>
                    <td><span class="staff-tag"><?= htmlspecialchars($row['processed_by'] ?? 'Unknown') ?></span></td>
                    <td>
                      <a href="print_receipt.php?group_id=<?= urlencode($row['group_id']) ?>" 
                         target="_blank" 
                         class="btn-receipt">
                         Print 🖨️
                      </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    function filterTable() {
        let input = document.getElementById('orderSearch').value.toUpperCase();
        let rows = document.getElementById('ordersTable').getElementsByTagName('tr');
        for (let i = 1; i < rows.length; i++) {
            let text = rows[i].textContent || rows[i].innerText;
            rows[i].style.display = text.toUpperCase().indexOf(input) > -1 ? "" : "none";
        }
    }
    </script>
</body>
</html>