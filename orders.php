<?php
include 'db.php';
include 'navbar.php';

// 1. Fetch products for the dropdown menu
$products_query = "SELECT id, product_name, price, stock_level FROM products WHERE stock_level > 0";
$products_result = $conn->query($products_query);

// 2. Fetch all orders (Grouped by group_id so one receipt doesn't take up 5 rows)
$orders_query = "SELECT group_id, customer_name, order_date, processed_by, SUM(total_price) as grand_total 
                 FROM orders 
                 GROUP BY group_id 
                 ORDER BY order_date DESC";
$orders_result = $conn->query($orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdfaf7; padding: 20px; color: #264653; }
        .container { max-width: 1100px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .header-accent { border-left: 5px solid #f4a261; padding-left: 15px; margin-bottom: 25px; font-weight: 600; }
        .input-box { width: 100%; padding: 12px; background: #e9f0fe; border: none; border-radius: 8px; margin-bottom: 15px; box-sizing: border-box; }
        .btn-add { background: #2a9d8f; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: 600; cursor: pointer; margin-bottom: 10px; }
        .btn-submit { background: #f4a261; color: white; border: none; padding: 12px; border-radius: 8px; width: 100%; font-weight: 600; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #264653; color: white; padding: 12px; text-align: left; font-size: 14px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .cart-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn-remove { color: #e76f51; cursor: pointer; font-weight: bold; margin-left: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; gap: 20px;">
        <div class="card" style="flex: 1;">
            <h2 class="header-accent">Select Items</h2>
            <select id="product_select" class="input-box">
                <option value="">-- Choose Bakery Item --</option>
                <?php while($p = $products_result->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['product_name']) ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock_level'] ?>">
                        <?= htmlspecialchars($p['product_name']) ?> (Stock: <?= $p['stock_level'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
            <input type="number" id="prod_qty" class="input-box" placeholder="Quantity" min="1" value="1">
            <button type="button" class="btn-add" onclick="addToCart()">Add to List +</button>
        </div>

        <div class="card" style="flex: 1;">
            <h2 class="header-accent">Customer Cart</h2>
            <div id="cart_items_list" style="min-height: 100px; margin-bottom: 15px;">
                <p style="color: #999; text-align: center;">Cart is empty</p>
            </div>
            <div style="font-weight: bold; font-size: 18px; margin-bottom: 15px; text-align: right;">
                Total: Ksh <span id="cart_total">0.00</span>
            </div>
            
            <form action="process_order.php" method="POST" id="checkout_form">
                <input type="hidden" name="cart_data" id="cart_data_input">
                <input type="text" name="customer_name" class="input-box" placeholder="Customer Name" required>
                <button type="submit" class="btn-submit">Complete Transaction</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2 class="header-accent">Recent Transactions</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Order ID</th>
                    <th>Total (Ksh)</th>
                    <th>Staff</th>
                    <th>Action</th> 
                </tr>
            </thead>
            <tbody>
                <?php while($row = $orders_result->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, H:i', strtotime($row['order_date'])) ?></td>
                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td><small><?= $row['group_id'] ?></small></td>
                    <td><?= number_format($row['grand_total'], 2) ?></td>
                    <td><?= htmlspecialchars($row['processed_by'] ?? 'N/A') ?></td>
                    <td>
                        <a href="print_receipt.php?group_id=<?= urlencode($row['group_id']) ?>" target="_blank">
                            <button class="btn-print" style="background:#2a9d8f; color:white; border:none; padding:5px 10px; border-radius:5px; cursor:pointer;">Print 🖨️</button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
let cart = [];

function addToCart() {
    const select = document.getElementById('product_select');
    const qtyInput = document.getElementById('prod_qty');
    const selectedOption = select.options[select.selectedIndex];

    if (!selectedOption.value) {
        alert("Please select a product!");
        return;
    }

    const productId = selectedOption.value;
    const name = selectedOption.getAttribute('data-name');
    const price = parseFloat(selectedOption.getAttribute('data-price'));
    const stock = parseInt(selectedOption.getAttribute('data-stock'));
    const qty = parseInt(qtyInput.value);

    if (qty > stock) {
        alert("Not enough stock! Available: " + stock);
        return;
    }

    // Check if item already in cart
    const existing = cart.find(item => item.id === productId);
    if (existing) {
        existing.qty += qty;
    } else {
        cart.push({ id: productId, name: name, price: price, qty: qty });
    }

    updateCartUI();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function updateCartUI() {
    const list = document.getElementById('cart_items_list');
    const totalDisplay = document.getElementById('cart_total');
    const cartInput = document.getElementById('cart_data_input');
    
    if (cart.length === 0) {
        list.innerHTML = '<p style="color: #999; text-align: center;">Cart is empty</p>';
        totalDisplay.innerText = "0.00";
        cartInput.value = "";
        return;
    }

    let total = 0;
    list.innerHTML = cart.map((item, index) => {
        total += (item.price * item.qty);
        return `
            <div class="cart-item">
                <span>${item.qty}x ${item.name}</span>
                <span>Ksh ${(item.price * item.qty).toFixed(2)} 
                    <span class="btn-remove" onclick="removeFromCart(${index})">×</span>
                </span>
            </div>`;
    }).join('');

    totalDisplay.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
    cartInput.value = JSON.stringify(cart);
}
</script>

</body>
</html>