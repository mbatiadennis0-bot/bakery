<?php
include 'db.php';
session_start();

// Security: Only dayknow/Admin can access
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'dayknow') {
    header("Location: inventory.php");
    exit();
}

// Fetch basic stats for the dashboard
$best_sellers = $conn->query("SELECT p.product_name, SUM(o.quantity) as total_sold 
                              FROM orders o JOIN products p ON o.product_id = p.id 
                              GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");

$attendance = $conn->query("SELECT * FROM attendance ORDER BY clock_in DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Center - Sweet Delights</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #264653;
            --accent: #f4a261;
            --danger: #e76f51;
            --bg: #fdf2e9; /* Warm bakery cream background */
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg); 
            background-image: radial-gradient(#f4a26122 1px, transparent 1px);
            background-size: 20px 20px;
            margin: 0; padding: 20px; 
        }

        .admin-container { max-width: 1100px; margin: auto; }

        .header-card {
            background: var(--primary);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-bottom: 5px solid var(--accent);
        }

        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            background: var(--accent);
            color: white;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 25px;
        }

        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; color: #888; font-size: 12px; text-transform: uppercase; padding-bottom: 10px; }
        td { padding: 12px 0; border-top: 1px solid #eee; font-size: 14px; }
    </style>
</head>
<body>

<div class="admin-container">
    <?php include 'navbar.php'; ?>

    <div class="header-card">
        <div>
            <h1 style="margin:0;">Admin Control Center</h1>
            <p style="margin:5px 0 0; opacity: 0.8;">Welcome back, <strong>dayknow</strong>. Here is your bakery overview.</p>
        </div>
        <span style="font-size: 40px;">🧁</span>
    </div>

    <div class="admin-nav-grid">
        <a href="reports.php" class="nav-card">
            <span style="font-size: 24px;">📅</span><br>Daily Sales Report
        </a>
        <a href="inventory.php" class="nav-card">
            <span style="font-size: 24px;">🍞</span><br>Add / Delete Products
        </a>
        <a href="view_orders.php" class="nav-card">
            <span style="font-size: 24px;">🛒</span><br>All Orders & Receipts
        </a>
    </div>

    <div class="dashboard-grid">
        <div class="stat-box">
            <h3 style="margin-top:0; color: var(--primary);">Best Sellers (Top 5)</h3>
            <canvas id="bestSellerChart" height="150"></canvas>
        </div>

        <div class="stat-box">
            <h3 style="margin-top:0; color: var(--primary);">Recent Staff Activity</h3>
            <table>
                <thead>
                    <tr><th>Staff Member</th><th>Clock In</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while($row = $attendance->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                        <td><?= date('H:i A', strtotime($row['clock_in'])) ?></td>
                        <td style="color: green; font-weight: 600;">Active</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('bestSellerChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: [<?php 
            $best_sellers->data_seek(0);
            while($b = $best_sellers->fetch_assoc()){ echo "'".$b['product_name']."',"; } 
        ?>],
        datasets: [{
            label: 'Units Sold',
            data: [<?php 
                $best_sellers->data_seek(0);
                while($b = $best_sellers->fetch_assoc()){ echo $b['total_sold'].","; } 
            ?>],
            backgroundColor: '#f4a261',
            borderRadius: 5
        }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>

</body>
</html>