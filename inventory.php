<?php
require_once 'auth.php';
require_once 'stock_workflow.php';
include 'db.php';

require_login();
ensure_stock_workflow_tables($conn);

$flashMessage = '';
$flashType = 'success';

if (isset($_GET['msg'])) {
    $flashMessage = $_GET['msg'];
    $flashType = $_GET['type'] ?? 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    if (isset($_POST['receive_stock'])) {
        $productId = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity_received'];
        $note = trim($_POST['receipt_note']);

        if ($productId < 1 || $quantity < 1) {
            header("Location: inventory.php?type=error&msg=" . urlencode('Choose a product and enter a valid quantity to receive.'));
            exit();
        }

        $conn->begin_transaction();
        try {
            $updateStmt = $conn->prepare("UPDATE products SET stock_level = stock_level + ? WHERE id = ?");
            $updateStmt->bind_param("ii", $quantity, $productId);
            $updateStmt->execute();
            $updateStmt->close();

            $logStmt = $conn->prepare("INSERT INTO stock_receipts (product_id, quantity_received, note, received_by) VALUES (?, ?, ?, ?)");
            $receivedBy = $_SESSION['username'];
            $logStmt->bind_param("iiss", $productId, $quantity, $note, $receivedBy);
            $logStmt->execute();
            $logStmt->close();

            $conn->commit();
            header("Location: inventory.php?msg=" . urlencode('Stock received successfully.'));
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            header("Location: inventory.php?type=error&msg=" . urlencode('Could not receive stock. Please try again.'));
            exit();
        }
    }

    if (isset($_POST['order_stock'])) {
        $productId = (int) $_POST['product_id'];
        $quantity = (int) $_POST['quantity_requested'];
        $supplierName = trim($_POST['supplier_name']);
        $supplierEmail = trim($_POST['supplier_email']);
        $note = trim($_POST['supplier_note']);

        if ($productId < 1 || $quantity < 1 || $supplierName === '' || !filter_var($supplierEmail, FILTER_VALIDATE_EMAIL)) {
            header("Location: inventory.php?type=error&msg=" . urlencode('Enter a valid supplier name, email, product, and quantity.'));
            exit();
        }

        $productStmt = $conn->prepare("SELECT product_name, category, stock_level FROM products WHERE id = ?");
        $productStmt->bind_param("i", $productId);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();
        $productStmt->close();

        if (!$product) {
            header("Location: inventory.php?type=error&msg=" . urlencode('Selected product was not found.'));
            exit();
        }

        $orderedBy = $_SESSION['username'];
        $subject = 'Bakery stock order request for ' . $product['product_name'];
        $htmlContent = '<html><body style="font-family:Poppins,Arial,sans-serif;">'
            . '<h2 style="color:#264653;">New Stock Order Request</h2>'
            . '<p>Hello ' . htmlspecialchars($supplierName) . ',</p>'
            . '<p>The bakery has placed a stock request for the item below:</p>'
            . '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;">'
            . '<tr><td><strong>Product</strong></td><td>' . htmlspecialchars($product['product_name']) . '</td></tr>'
            . '<tr><td><strong>Category</strong></td><td>' . htmlspecialchars((string) $product['category']) . '</td></tr>'
            . '<tr><td><strong>Quantity Requested</strong></td><td>' . $quantity . '</td></tr>'
            . '<tr><td><strong>Current Stock</strong></td><td>' . (int) $product['stock_level'] . '</td></tr>'
            . '<tr><td><strong>Ordered By</strong></td><td>' . htmlspecialchars($orderedBy) . '</td></tr>'
            . '</table>'
            . ($note !== '' ? '<p><strong>Note:</strong> ' . nl2br(htmlspecialchars($note)) . '</p>' : '')
            . '<p>Please confirm availability and delivery timing.</p>'
            . '<p>Thank you,<br>Bakery System</p>'
            . '</body></html>';

        $textContent = "New Stock Order Request\n"
            . "Product: {$product['product_name']}\n"
            . "Category: {$product['category']}\n"
            . "Quantity Requested: {$quantity}\n"
            . "Current Stock: {$product['stock_level']}\n"
            . "Ordered By: {$orderedBy}\n"
            . ($note !== '' ? "Note: {$note}\n" : '');

        $emailResult = send_brevo_email($supplierEmail, $supplierName, $subject, $htmlContent, $textContent, true);

        $status = $emailResult['success'] ? 'sent' : 'failed';
        $messageId = $emailResult['message_id'] ?? null;
        $brevoStatusCode = $emailResult['status_code'] ?? 0;
        $brevoError = $emailResult['success'] ? null : ($emailResult['error'] . (!empty($emailResult['response_body']) ? ' | Response: ' . $emailResult['response_body'] : ''));

        $orderStmt = $conn->prepare("INSERT INTO supplier_orders (product_id, supplier_name, supplier_email, quantity_requested, note, ordered_by, brevo_message_id, delivery_status, brevo_status_code, brevo_error) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $orderStmt->bind_param(
            "ississssis",
            $productId,
            $supplierName,
            $supplierEmail,
            $quantity,
            $note,
            $orderedBy,
            $messageId,
            $status,
            $brevoStatusCode,
            $brevoError
        );
        $orderStmt->execute();
        $orderStmt->close();

        if ($emailResult['success']) {
            header("Location: inventory.php?msg=" . urlencode('Supplier order sent successfully by Brevo, and a copy was sent to your email.'));
            exit();
        }

        $debugMessage = 'Stock order was logged, but Brevo could not send the email: ' . $emailResult['error'];
        if (!empty($emailResult['status_code'])) {
            $debugMessage .= ' (HTTP ' . $emailResult['status_code'] . ')';
        }
        header("Location: inventory.php?type=error&msg=" . urlencode($debugMessage));
        exit();
    }
}

if (isset($_GET['delete'])) {
    require_admin();
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: inventory.php?msg=" . urlencode('Product deleted.'));
    exit();
}

$products = $conn->query("SELECT * FROM products ORDER BY product_name ASC");
$productOptions = $conn->query("SELECT id, product_name, category, stock_level FROM products ORDER BY product_name ASC");
$recentReceipts = $conn->query("SELECT sr.quantity_received, sr.note, sr.received_by, sr.received_at, p.product_name
                                FROM stock_receipts sr
                                JOIN products p ON p.id = sr.product_id
                                ORDER BY sr.received_at DESC
                                LIMIT 6");
$recentSupplierOrders = $conn->query("SELECT so.supplier_name, so.supplier_email, so.quantity_requested, so.delivery_status, so.created_at, so.brevo_status_code, so.brevo_error, p.product_name
                                      FROM supplier_orders so
                                      JOIN products p ON p.id = so.product_id
                                      ORDER BY so.created_at DESC
                                      LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory | Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; }
        .page-shell { max-width: 1180px; margin: 0 auto; }
        .inventory-card, .workflow-card {
            background: rgba(255, 249, 243, 0.9);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            color: var(--bakery-primary);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .workflow-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }
        .workflow-card h3, .inventory-card h2 {
            margin-top: 0;
            color: #264653;
        }
        .form-grid {
            display: grid;
            gap: 12px;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #ddd;
            font: inherit;
        }
        textarea { min-height: 90px; resize: vertical; }
        .action-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
        .btn-add, .btn-action {
            background: linear-gradient(135deg, #f4a261, #e76f51);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        .btn-secondary {
            background: #264653;
        }
        .notice {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 600;
        }
        .notice.success { background: #eaf8f4; color: #1c7d70; }
        .notice.error { background: #fff1eb; color: #b04c37; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--bakery-primary);
            vertical-align: top;
        }
        tr:hover { background: rgba(255, 255, 255, 0.05); }
        .badge {
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 18px;
            margin-top: 18px;
        }
        .mini-log {
            background: rgba(255, 249, 243, 0.9);
            padding: 24px;
            border-radius: 24px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .mini-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(38, 70, 83, 0.08);
        }
        .mini-item:last-child { border-bottom: none; }
        .muted { color: #7b8b8e; font-size: 13px; }
        .sender-warning {
            margin-top: 12px;
            font-size: 13px;
            color: #8a4b2b;
            background: #fff2e1;
            border-radius: 12px;
            padding: 12px;
        }
        @media (max-width: 980px) {
            .workflow-grid, .two-column { grid-template-columns: 1fr; }
            .action-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include 'navbar.php'; ?>

        <?php if ($flashMessage !== ''): ?>
            <div class="notice <?= $flashType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($flashMessage) ?></div>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <div class="workflow-grid">
                <section class="workflow-card">
                    <h3>Receive Stock</h3>
                    <p class="muted">Add newly delivered stock into inventory and keep a record of who received it.</p>
                    <form method="POST" class="form-grid">
                        <select name="product_id" required>
                            <option value="">Select product</option>
                            <?php if ($productOptions): $productOptions->data_seek(0); ?>
                                <?php while ($option = $productOptions->fetch_assoc()): ?>
                                    <option value="<?= (int) $option['id'] ?>">
                                        <?= htmlspecialchars($option['product_name']) ?> (Current: <?= (int) $option['stock_level'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <input type="number" name="quantity_received" min="1" placeholder="Quantity received" required>
                        <textarea name="receipt_note" placeholder="Delivery note, batch note, or comments"></textarea>
                        <button type="submit" name="receive_stock" class="btn-action btn-secondary">Receive Stock</button>
                    </form>
                </section>

                <section class="workflow-card">
                    <h3>Order Stock From Supplier</h3>
                    <p class="muted">Create a supplier order and send an email through Brevo so the supplier is notified immediately.</p>
                    <form method="POST" class="form-grid">
                        <select name="product_id" required>
                            <option value="">Select product</option>
                            <?php if ($productOptions): $productOptions->data_seek(0); ?>
                                <?php while ($option = $productOptions->fetch_assoc()): ?>
                                    <option value="<?= (int) $option['id'] ?>">
                                        <?= htmlspecialchars($option['product_name']) ?> (Current: <?= (int) $option['stock_level'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <input type="number" name="quantity_requested" min="1" placeholder="Quantity to order" required>
                        <input type="text" name="supplier_name" placeholder="Supplier name" required>
                        <input type="email" name="supplier_email" placeholder="Supplier email" required>
                        <textarea name="supplier_note" placeholder="Extra supplier note or delivery request"></textarea>
                        <button type="submit" name="order_stock" class="btn-action">Order Stock Via Brevo</button>
                    </form>
                    <?php if (!brevo_sender_ready()): ?>
                        <div class="sender-warning">
                            Update the verified sender email in <strong>brevo_config.php</strong> before live supplier emails can be sent.
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>

        <div class="two-column">
            <section class="inventory-card">
                <div class="action-row">
                    <h2>Stock Inventory</h2>
                    <?php if (is_admin()): ?>
                        <a href="add_product.php" class="btn-add">+ Add New Product</a>
                    <?php endif; ?>
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
                        <?php while ($row = $products->fetch_assoc()): ?>
                            <?php
                            $stock = (int) ($row['stock_level'] ?? 0);
                            $isLow = $stock <= 5;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['category'] ?? 'Bakery') ?></td>
                                <td>Ksh <?= number_format((float) $row['price'], 2) ?></td>
                                <td><?= $stock ?> units</td>
                                <td>
                                    <span class="badge" style="background: <?= $isLow ? '#fff0eb' : '#eaf8f4' ?>; color: <?= $isLow ? '#e76f51' : '#2a9d8f' ?>;">
                                        <?= $isLow ? 'Low Stock' : 'In Stock' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (is_admin()): ?>
                                        <a href="edit_product.php?id=<?= (int) $row['id'] ?>" style="text-decoration:none;">Edit</a>
                                        <a href="inventory.php?delete=<?= (int) $row['id'] ?>" onclick="return confirm('Are you sure?')" style="text-decoration:none; margin-left:10px;">Delete</a>
                                    <?php else: ?>
                                        <span class="muted">View Only</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <div style="display:grid; gap:18px;">
                <section class="mini-log">
                    <h3 style="margin-top:0;">Recent Stock Received</h3>
                    <?php if ($recentReceipts && $recentReceipts->num_rows > 0): ?>
                        <?php while ($receipt = $recentReceipts->fetch_assoc()): ?>
                            <div class="mini-item">
                                <strong><?= htmlspecialchars($receipt['product_name']) ?> +<?= (int) $receipt['quantity_received'] ?></strong>
                                <div class="muted">By <?= htmlspecialchars($receipt['received_by']) ?> on <?= date('d M, h:i A', strtotime($receipt['received_at'])) ?></div>
                                <?php if (!empty($receipt['note'])): ?><div class="muted"><?= htmlspecialchars($receipt['note']) ?></div><?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="muted">No stock receipts logged yet.</div>
                    <?php endif; ?>
                </section>

                <section class="mini-log">
                    <h3 style="margin-top:0;">Recent Supplier Orders</h3>
                    <?php if ($recentSupplierOrders && $recentSupplierOrders->num_rows > 0): ?>
                        <?php while ($supplierOrder = $recentSupplierOrders->fetch_assoc()): ?>
                            <div class="mini-item">
                                <strong><?= htmlspecialchars($supplierOrder['product_name']) ?> x<?= (int) $supplierOrder['quantity_requested'] ?></strong>
                                <div class="muted"><?= htmlspecialchars($supplierOrder['supplier_name']) ?> (<?= htmlspecialchars($supplierOrder['supplier_email']) ?>)</div>
                                <div class="muted"><?= date('d M, h:i A', strtotime($supplierOrder['created_at'])) ?> - <?= htmlspecialchars($supplierOrder['delivery_status']) ?><?= !empty($supplierOrder['brevo_status_code']) ? ' (HTTP ' . (int) $supplierOrder['brevo_status_code'] . ')' : '' ?></div>
                                <?php if (!empty($supplierOrder['brevo_error'])): ?><div class="muted"><?= htmlspecialchars($supplierOrder['brevo_error']) ?></div><?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="muted">No supplier orders logged yet.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</body>
</html>
