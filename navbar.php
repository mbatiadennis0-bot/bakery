<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
$username = $_SESSION['username'] ?? 'Guest';

// Admin-only check for low stock (items with 5 or fewer units)
$low_stock_count = 0;
if ($username === 'dayknow') {
    $res = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock_level <= 5");
    $row = $res->fetch_assoc();
    $low_stock_count = $row['total'];
}
?>
<nav style="background: #264653; padding: 15px 30px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; font-family: 'Poppins', sans-serif; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">🥐</span>
        <h2 style="color: #f4a261; margin: 0; font-size: 20px;">Bakery System</h2>
    </div>

    <div style="display: flex; gap: 20px; align-items: center;">
        <a href="inventory.php" style="color: white; text-decoration: none; font-size: 14px; transition: 0.3s;">Inventory</a>
        <a href="view_orders.php" style="color: white; text-decoration: none; font-size: 14px;">Orders</a>
        <a href="pos.php" style="background: #2a9d8f; color: white; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600;">🛒 New Order</a>
        <a href="attendance.php" style="color: white; text-decoration: none; font-size: 14px;">Attendance</a>

        <?php if($username === 'dayknow'): ?>
            <div style="width: 1px; height: 20px; background: rgba(255,255,255,0.2); margin: 0 5px;"></div>
            <a href="admin_dashboard.php" style="color: #f4a261; text-decoration: none; font-weight: 600; font-size: 14px;">Dashboard</a>
            <a href="reports.php" style="color: #f4a261; text-decoration: none; font-weight: 600; font-size: 14px;">Reports</a>
            
            <?php if($low_stock_count > 0): ?>
                <a href="inventory.php" title="Low Stock Warning" style="background: #e76f51; color: white; padding: 2px 8px; border-radius: 50px; font-size: 11px; text-decoration: none; font-weight: bold;">
                    ⚠️ <?= $low_stock_count ?> Low
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="display: flex; align-items: center; gap: 15px;">
        <div style="text-align: right;">
            <div style="color: white; font-size: 14px; font-weight: 600;"><?= htmlspecialchars($username); ?></div>
            <div style="color: #f4a261; font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">
                <?= ($username === 'dayknow') ? 'Administrator' : 'Staff' ?>
            </div>
        </div>
<a href="logout.php">Logout</a>
    </div>
</nav>