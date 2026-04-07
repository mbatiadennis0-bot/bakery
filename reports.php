<?php
require_once 'auth.php';
include 'db.php'; 

require_admin();
include 'navbar.php'; 

// 3. Fetch Total Revenue (All time)
$rev_query = "SELECT SUM(total_price) as total FROM orders";
$rev_res = $conn->query($rev_query);
$rev_data = $rev_res->fetch_assoc();
$revenue = $rev_data['total'] ?? 0;

// 4. Fetch New Orders Today (Unique Receipts/Groups)
$today_date = date('Y-m-d');
$order_query = "SELECT COUNT(DISTINCT group_id) as total_today FROM orders WHERE DATE(order_date) = '$today_date'";
$order_res = $conn->query($order_query);
$order_data = $order_res->fetch_assoc();
$today_orders = $order_data['total_today'] ?? 0;

// 5. Fetch Total Customers served
$cust_query = "SELECT COUNT(DISTINCT customer_name) as total_customers FROM orders";
$cust_res = $conn->query($cust_query);
$cust_data = $cust_res->fetch_assoc();
$total_customers = $cust_data['total_customers'] ?? 0;

// 6. Prepare Data for the Top 5 Chart
$labels = [];
$counts = [];
$top_query = "SELECT p.product_name, SUM(o.quantity) as total_sold 
              FROM orders o 
              JOIN products p ON o.product_id = p.id 
              GROUP BY o.product_id 
              ORDER BY total_sold DESC LIMIT 5";
$top_res = $conn->query($top_query);

while ($row = $top_res->fetch_assoc()) {
    $labels[] = $row['product_name'];
    $counts[] = (int)$row['total_sold'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Insights - Sweet Delights</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #264653;
            --accent: #f4a261;
            --danger: #e76f51;
            --bg: #fdf2e9; 
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; padding: 20px; 
        }

        .container { max-width: 1100px; margin: 20px auto; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 35px; }
        
        .stat-card { 
            background: rgba(255, 250, 245, 0.94); padding: 30px; border-radius: 24px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; 
            border-bottom: 6px solid var(--accent); transition: 0.3s; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px);
        }
        .stat-card:hover { transform: translateY(-8px); }
        .stat-card h3 { color: #888; font-size: 12px; text-transform: uppercase; margin: 0 0 12px 0; letter-spacing: 1.5px; font-weight: 700; }
        .stat-card p { color: var(--primary); font-size: 30px; font-weight: 700; margin: 0; }
        
        /* Layout Sections */
        .analytics-main { display: grid; grid-template-columns: 1.2fr 1.8fr; gap: 30px; }
        .card-panel { background: rgba(255, 250, 245, 0.94); padding: 25px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px); }
        
        h2 { color: var(--primary); font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        h3 { font-size: 16px; color: var(--primary); margin-bottom: 20px; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: #aaa; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #fdf2e9; }
        td { padding: 15px 12px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }
        
        .badge-item { background: #f4a26122; color: #e76f51; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 11px; }
        .amt-text { font-weight: 700; color: #2a9d8f; }

        @media print { .navbar, .print-hide { display: none; } body { background: white; } }
    </style>
</head>
<body>

<div class="container">
    <h2><span style="font-size:30px;">📊</span> Manager Business Insights</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Lifetime Revenue</h3>
            <p>Ksh <?php echo number_format($revenue, 2); ?></p>
        </div>
        <div class="stat-card" style="border-bottom-color: #2a9d8f;">
            <h3>Sales Today</h3>
            <p><?php echo $today_orders; ?> <small style="font-size:14px; color:#888;">Receipts</small></p>
        </div>
        <div class="stat-card" style="border-bottom-color: #e76f51;">
            <h3>Unique Customers</h3>
            <p><?php echo $total_customers; ?></p>
        </div>
    </div>

    <div class="analytics-main">
        <div class="card-panel">
            <h3>🔥 Top 5 Bakery Items</h3>
            <canvas id="salesChart" height="250"></canvas>
            <button onclick="window.print()" class="print-hide" style="margin-top:25px; width:100%; padding:12px; border:none; background:var(--primary); color:white; border-radius:10px; font-weight:600; cursor:pointer;">🖨️ Export PDF Report</button>
        </div>

        <div class="card-panel">
            <h3>🕒 Latest Bakery Transactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = $conn->query("SELECT o.*, p.product_name FROM orders o JOIN products p ON o.product_id = p.id ORDER BY o.order_date DESC LIMIT 7");
                    if ($recent && $recent->num_rows > 0) {
                        while($row = $recent->fetch_assoc()) {
                            echo "<tr>
                                <td>
                                    <div style='font-weight:700;'>".htmlspecialchars($row['customer_name'])."</div>
                                    <div style='font-size:10px; color:#aaa;'>".date('d M, H:i', strtotime($row['order_date']))."</div>
                                </td>
                                <td><span class='badge-item'>".htmlspecialchars($row['product_name'])." (x".$row['quantity'].")</span></td>
                                <td class='amt-text'>Ksh ".number_format($row['total_price'], 2)."</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; padding:20px;'>No transactions yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Total Units Sold',
            data: <?php echo json_encode($counts); ?>,
            backgroundColor: '#f4a261', 
            hoverBackgroundColor: '#e76f51',
            borderRadius: 8,
        }]
    },
    options: {
        indexAxis: 'y', // Makes it a horizontal bar chart for better readability
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { display: false } },
            y: { grid: { display: false } }
        }
    }
});
</script>

</body>
</html>
