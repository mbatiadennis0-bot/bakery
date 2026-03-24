<?php
session_start();
include 'db.php';

// 1. Security: Only Admin (like dayknow) can see the detailed money report
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Set Timezone for Kenya
date_default_timezone_set('Africa/Nairobi');
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report - <?php echo $today; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; padding: 40px; background: #f4f4f4; }
        .report-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 900px; margin: auto; }
        h1 { color: #264653; border-bottom: 3px solid #f4a261; padding-bottom: 10px; margin-top: 0; }
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 30px 0; }
        .stat-card { background: #fff9f4; padding: 25px; border: 1px solid #f4a261; border-radius: 12px; text-align: center; }
        .stat-card h3 { margin: 0; color: #e76f51; font-size: 16px; text-transform: uppercase; }
        .stat-card h2 { margin: 10px 0 0; color: #264653; font-size: 28px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #264653; color: white; border-radius: 5px 5px 0 0; }
        
        .btn-group { float: right; display: flex; gap: 10px; }
        .print-btn { background: #2a9d8f; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .back-btn { background: #264653; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-size: 14px; }

        /* Hide buttons during printing */
        @media print {
            .btn-group, .back-btn { display: none; }
            body { background: white; padding: 0; }
            .report-box { box-shadow: none; border: none; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="report-box">
    <div class="btn-group">
        <a href="dashboard.php" class="back-btn">← Dashboard</a>
        <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
    </div>

    <h1>Bakery Daily Sales Summary</h1>
    <p><strong>Generated on:</strong> <?php echo date('D, M j, Y | h:i A'); ?></p>

    <div class="stat-grid">
        <div class="stat-card">
            <h3>Total Revenue (Today)</h3>
            <?php 
            // Query assumes your orders table uses 'order_date' or 'created_at'
            $rev = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE DATE(order_date) = '$today'");
            $r = $rev->fetch_assoc();
            echo "<h2>Ksh " . number_format($r['total'] ?? 0, 2) . "</h2>";
            ?>
        </div>
        <div class="stat-card">
            <h3>Total Items Sold</h3>
            <?php 
            $count = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = '$today'");
            $c = $count->fetch_assoc();
            echo "<h2>" . ($c['total'] ?? 0) . "</h2>";
            ?>
        </div>
    </div>

    <h3>Sales Performance by Staff</h3>
    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Products Sold</th>
                <th>Total Cash Collected</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Updated to group by the 'processed_by' column which tracks who was logged in
            $staff = $conn->query("SELECT processed_by, COUNT(*) as orders, SUM(total_price) as cash 
                                   FROM orders 
                                   WHERE DATE(order_date) = '$today' 
                                   GROUP BY processed_by");
            
            if ($staff && $staff->num_rows > 0):
                while($row = $staff->fetch_assoc()):
                    $name = !empty($row['processed_by']) ? htmlspecialchars($row['processed_by']) : "System/Walk-in";
                    echo "<tr>
                            <td><strong>$name</strong></td>
                            <td>{$row['orders']} items</td>
                            <td><strong>Ksh " . number_format($row['cash'], 2) . "</strong></td>
                          </tr>";
                endwhile;
            else:
                echo "<tr><td colspan='3' style='text-align:center; padding: 20px;'>No sales recorded yet for today.</td></tr>";
            endif;
            ?>
        </tbody>
    </table>
</div>

</body>
</html>