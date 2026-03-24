<?php
session_start();
include 'db.php';

// 1. ACCESS CONTROL: Strict Admin-only access
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// 2. FILTER LOGIC
$filter = $_GET['filter'] ?? 'all';
$date_condition = "";

switch ($filter) {
    case 'today':
        $date_condition = " AND DATE(order_date) = CURDATE()";
        $title = "Today's Performance";
        break;
    case 'week':
        $date_condition = " AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $title = "Last 7 Days";
        break;
    case 'month':
        $date_condition = " AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $title = "Last 30 Days";
        break;
    default:
        $date_condition = "";
        $title = "All-Time Performance";
}

// 3. REVENUE CALCULATION (Using Prepared Statements is safer)
$rev_stmt = $conn->prepare("SELECT SUM(total_price) as grand_total FROM orders WHERE 1=1 $date_condition");
$rev_stmt->execute();
$total_rev = $rev_stmt->get_result()->fetch_assoc()['grand_total'] ?? 0;

// 4. EMPLOYEE PERFORMANCE QUERY
// Added a check to ensure we only count unique Order IDs to avoid double-counting
$perf_query = "SELECT users.username, 
              COUNT(DISTINCT orders.order_group_id) as total_orders, 
              SUM(orders.total_price) as total_revenue 
              FROM users 
              LEFT JOIN orders ON users.username = orders.processed_by $date_condition
              WHERE users.role != 'Admin' 
              GROUP BY users.username 
              ORDER BY total_revenue DESC";
$perf_result = $conn->query($perf_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Insights - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdf2e9; margin: 0; padding: 20px; color: #264653; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        
        .filter-bar { background: #fff9f4; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #f4a261; }
        select { padding: 12px; border-radius: 10px; border: 1px solid #ddd; font-weight: 600; color: #264653; cursor: pointer; outline: none; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; margin-bottom: 35px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 18px; box-shadow: 0 5px 15px rgba(0,0,0,0.04); border-left: 8px solid #2a9d8f; position: relative; }
        .stat-card small { color: #666; text-transform: uppercase; font-size: 11px; font-weight: 600; letter-spacing: 1.2px; }
        .stat-card p { margin: 10px 0 0; font-size: 28px; font-weight: 600; color: #264653; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 10px; }
        th { background: #264653; color: white; padding: 18px; text-align: left; font-size: 14px; text-transform: uppercase; }
        th:first-child { border-radius: 10px 0 0 10px; }
        th:last-child { border-radius: 0 10px 10px 0; }
        
        td { padding: 18px; background: #fafafa; border-top: 1px solid #eee; border-bottom: 1px solid #eee; }
        td:first-child { border-left: 1px solid #eee; border-radius: 10px 0 0 10px; }
        td:last-child { border-right: 1px solid #eee; border-radius: 0 10px 10px 0; }
        
        .badge { padding: 6px 14px; border-radius: 25px; font-size: 11px; font-weight: 700; }
        .badge-active { background: #e9f5f2; color: #2a9d8f; }
        .badge-zero { background: #fff5f5; color: #e76f51; }
    </style>
</head>
<body>

<div class="container">
    <?php include 'navbar.php'; ?>

    <div class="filter-bar">
        <h2 style="margin:0;"><?php echo $title; ?></h2>
        <form method="GET">
            <select name="filter" onchange="this.form.submit()">
                <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>Overall Performance</option>
                <option value="today" <?php echo ($filter == 'today') ? 'selected' : ''; ?>>Today's Sales</option>
                <option value="week" <?php echo ($filter == 'week') ? 'selected' : ''; ?>>Weekly Report</option>
                <option value="month" <?php echo ($filter == 'month') ? 'selected' : ''; ?>>Monthly Report</option>
            </select>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <small>Total Revenue</small>
            <p>Ksh <?php echo number_format($total_rev, 2); ?></p>
        </div>
        
        <div class="stat-card" style="border-left-color: #f4a261;">
            <small>Star Salesperson</small>
            <?php 
            $perf_result->data_seek(0);
            $top = $perf_result->fetch_assoc();
            ?>
            <p>
                <?php echo ($top && ($top['total_revenue'] ?? 0) > 0) ? htmlspecialchars($top['username']) : 'No Sales Yet'; ?>
            </p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Staff Member</th>
                <th>Total Orders</th>
                <th>Revenue (Ksh)</th>
                <th>Performance Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $perf_result->data_seek(0); // Reset result pointer for the table
            if ($perf_result->num_rows > 0):
                while($row = $perf_result->fetch_assoc()): 
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                <td><?php echo $row['total_orders'] ?? 0; ?></td>
                <td><?php echo number_format($row['total_revenue'] ?? 0, 2); ?></td>
                <td>
                    <?php if(($row['total_revenue'] ?? 0) > 0): ?>
                        <span class="badge badge-active">ACTIVE SELLER</span>
                    <?php else: ?>
                        <span class="badge badge-zero">WAITING FOR SALES</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="4" style="text-align:center;">No staff members registered in the system.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>