<?php
// 1. Include database connection
include 'db.php';

// 2. Check if group_id is provided in the URL
if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
    die("Error: No order ID provided. Please go back to the orders page.");
}

$group_id = mysqli_real_escape_string($conn, $_GET['group_id']);

/**
 * FIXED QUERY:
 * Added 'IFNULL' for product names in case a product was deleted.
 * Ensure your 'products' table actually has a column named 'price'.
 */
$query = "SELECT orders.*, 
          IFNULL(products.product_name, 'Unknown Product') as product_name, 
          IFNULL(products.price, 0) as unit_price 
          FROM orders 
          LEFT JOIN products ON orders.product_id = products.id 
          WHERE orders.group_id = '$group_id'";

$result = $conn->query($query);

// 3. Error handling: If no rows are found, it means the order wasn't saved yet
if (!$result || $result->num_rows == 0) {
    die("Error: Order not found in the database. (Group ID: " . htmlspecialchars($group_id) . ")");
}

$all_rows = [];
while($row = $result->fetch_assoc()) {
    $all_rows[] = $row;
}

// 4. Extract global order data from the first row
$first_order = $all_rows[0];
$customer_name = $first_order['customer_name'];
$order_date = $first_order['order_date'];
// FIXED: Using 'processed_by' to match your process_order.php and DB schema
$served_by = $first_order['processed_by'] ?? 'Staff'; 
$status = "Paid"; 
$qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($group_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt_<?php echo htmlspecialchars($group_id); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { background: #fdf5e6; font-family: 'Poppins', sans-serif; display: flex; flex-direction: column; align-items: center; padding: 20px; margin: 0; }
        .receipt-card { 
            background: white; width: 350px; padding: 30px; border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 10px solid #264653;
            position: relative;
        }
        .logo-container { text-align: center; margin-bottom: 20px; }
        .logo-icon { font-size: 50px; color: #e76f51; }
        .brand-name { font-family: 'Dancing Script', cursive; font-size: 32px; color: #264653; margin: 0; }
        .divider { border-top: 2px dashed #eee; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #888; font-size: 11px; text-transform: uppercase; padding-bottom: 10px; }
        td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f9f9f9; }
        .total-section { background: #f4a26115; padding: 15px; border-radius: 10px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; font-weight: 600; font-size: 18px; color: #264653; }
        .qr-section { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; }
        .qr-code { width: 90px; height: 90px; margin-bottom: 8px; border: 4px solid white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-badge { display: inline-block; background: #2a9d8f; color: white; padding: 3px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; margin-top: 5px; }
        
        .btn-group { margin-top: 20px; width: 350px; display: flex; flex-direction: column; gap: 10px; }
        .no-print-btn {
            background: #264653; color: white; border: none; padding: 12px;
            border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;
            text-align: center; text-decoration: none; transition: 0.3s;
        }
        .secondary-btn { background: #f4a261; }
        .no-print-btn:hover { opacity: 0.9; }

        @media print {
            body { background: white; padding: 0; }
            .receipt-card { box-shadow: none; border-top: none; width: 100%; padding: 10px; }
            .btn-group { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="logo-container">
        <div class="logo-icon">🧁</div>
        <h1 class="brand-name">Sweet Delights</h1>
        <small style="color: #e76f51; font-weight: 600; letter-spacing: 2px;">BAKERY & CONFECTIONERY</small>
    </div>

    <div style="font-size: 12px; color: #555; line-height: 1.6;">
        <strong>Receipt #:</strong> <?php echo strtoupper(htmlspecialchars($group_id)); ?><br>
        <strong>Date:</strong> <?php echo date('D, d M Y | H:i', strtotime($order_date)); ?><br>
        <strong>Customer:</strong> <?php echo htmlspecialchars($customer_name); ?><br>
        <strong>Served by:</strong> <?php echo htmlspecialchars($served_by); ?>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total = 0;
            foreach($all_rows as $row): 
                $grand_total += $row['total_price'];
            ?>
            <tr>
                <td>
                    <span style="font-weight: 600; color: #264653;"><?php echo htmlspecialchars($row['product_name']); ?></span><br>
                    <small style="color: #999;">Ksh <?php echo number_format($row['unit_price'], 2); ?> ea</small>
                </td>
                <td style="text-align: center;"><?php echo $row['quantity']; ?></td>
                <td style="text-align: right; font-weight: 600;">Ksh <?php echo number_format($row['total_price'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>TOTAL DUE</span>
            <span>Ksh <?php echo number_format($grand_total, 2); ?></span>
        </div>
        <div style="text-align: right;">
            <span class="status-badge"><?php echo strtoupper($status); ?></span>
        </div>
    </div>

    <div class="qr-section">
        <img src="<?php echo $qr_api; ?>" alt="Receipt QR" class="qr-code">
        <p style="font-size: 12px; color: #264653; font-weight: 600; margin: 5px 0 0;">Get 10% Off Next Time!</p>
    </div>

    <div style="text-align: center; margin-top: 25px; font-size: 11px; color: #bbb;">
        Thank you for choosing Sweet Delights!
    </div>
</div>

<div class="btn-group">
    <button class="no-print-btn" onclick="window.print()">Print Receipt 🖨️</button>
    <a href="view_orders.php" class="no-print-btn secondary-btn">View All Transactions ←</a>
    <a href="pos.php" class="no-print-btn" style="background: #2a9d8f;">New Sale +</a>
</div>

</body>
</html>