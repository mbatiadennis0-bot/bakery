<?php
session_start();
include 'db.php';

// 1. Strict Security Check
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// 2. Updated Query: Using product_name to link tables
// Since your orders table currently stores names instead of IDs
$best_sellers_query = "SELECT 
                        p.product_name, 
                        p.category, 
                        COUNT(o.id) as total_sales_count, 
                        SUM(o.total_price) as total_revenue
                      FROM products p
                      JOIN orders o ON p.product_name = o.product_name
                      GROUP BY p.product_name 
                      ORDER BY total_revenue DESC";

$result = $conn->query($best_sellers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Best Sellers - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #fdf2e9; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        .stats-card { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h2 { color: #264653; border-left: 5px solid #e76f51; padding-left: 15px; }
        .btn-back { text-decoration: none; background: #264653; color: white; padding: 8px 15px; border-radius: 8px; font-size: 14px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 18px 15px; background: #264653; color: white; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #444; }
        
        /* Highlight the #1 Best Seller */
        .rank-1 { background: #fff9e6; font-weight: 600; color: #b08d00; } 
        .rank-1 td { border-bottom: 2px solid #f4a261; }
        .medal { font-size: 20px; margin-right: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>Product Sales Performance</h2>
        <a href="dashboard.php" class="btn-back">← Dashboard</a>
    </div>

    <div class="stats-card">
        <p style="color: #666;">Top performing items by total revenue.</p>

        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Orders</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rank = 1;
                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()): 
                        $row_class = ($rank == 1) ? 'rank-1' : '';
                        $medal = ($rank == 1) ? '🥇' : (($rank == 2) ? '🥈' : (($rank == 3) ? '🥉' : ''));
                ?>
                <tr class="<?php echo $row_class; ?>">
                    <td><?php echo $rank . " " . $medal; ?></td>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo $row['total_sales_count']; ?></td>
                    <td><strong>Ksh <?php echo number_format($row['total_revenue'], 2); ?></strong></td>
                </tr>
                <?php 
                    $rank++;
                    endwhile; 
                else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No sales data available yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>