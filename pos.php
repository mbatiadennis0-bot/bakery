<?php
require_once 'auth.php';
include 'db.php';

require_active_shift($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order | Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="theme.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; padding: 20px; }
        .main-container { display: flex; gap: 20px; max-width: 1200px; margin: auto; }
        
        /* Products Area */
        .products-section { flex: 2; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .item-card { background: rgba(255, 250, 245, 0.94); padding: 20px; border-radius: 20px; text-align: center; box-shadow: 0 12px 28px rgba(0,0,0,0.08); border: 1px solid rgba(255,255,255,0.2); transition: 0.3s; backdrop-filter: blur(8px); }
        .item-card:hover { border-color: #f4a261; transform: translateY(-3px); }
        .btn-add { background: #2a9d8f; color: white; border: none; padding: 10px; width: 100%; border-radius: 8px; cursor: pointer; font-weight: 600; margin-top: 10px; }

        /* Cart Sidebar */
        .cart-section { flex: 1; background: rgba(255, 250, 245, 0.95); padding: 25px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); height: fit-content; position: sticky; top: 20px; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(8px); }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .cart-total { margin-top: 20px; padding-top: 15px; border-top: 2px solid #264653; font-weight: bold; font-size: 18px; display: flex; justify-content: space-between; }
        .btn-checkout { background: #e76f51; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 20px; }
        
        /* Remove Button */
        .btn-remove { color: #e74c3c; cursor: pointer; font-weight: bold; margin-left: 10px; font-size: 12px; border: none; background: none; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="main-container">
        <div class="products-section">
            <h2 style="color: #264653; margin-bottom: 20px;">🛒 Select Products</h2>
            <div class="grid">
                <?php
                $products = $conn->query("SELECT * FROM products WHERE stock_level > 0");
                while($p = $products->fetch_assoc()):
                ?>
                <div class="item-card">
                    <span style="color: #f4a261; font-size: 11px; font-weight: bold; text-transform: uppercase;"><?= $p['category'] ?></span>
                    <h4 style="margin: 5px 0; color: #264653;"><?= $p['product_name'] ?></h4>
                    <p style="font-weight: bold; margin: 5px 0;">Ksh <?= number_format($p['price'], 2) ?></p>
                    <p style="font-size: 12px; color: #888;">Stock: <?= $p['stock_level'] ?></p>
                    
                    <button class="btn-add" onclick="addToCart(<?= $p['id'] ?>, '<?= $p['product_name'] ?>', <?= $p['price'] ?>)">
                        Add to Cart
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="cart-section">
            <h3 style="margin-top: 0; color: #264653;">Your Cart</h3>
            <div id="cart-items">
                <p style="color: #999; text-align: center;">Cart is empty</p>
            </div>
            
            <div class="cart-total">
                <span>Total</span>
                <span>Ksh <span id="total-amount">0.00</span></span>
            </div>

            <form action="process_order.php" method="POST" id="checkout-form">
                <input type="hidden" name="cart_data" id="cart-data-input">
                <input type="text" name="customer_name" placeholder="Customer Name" required style="width: 100%; padding: 12px; margin-top: 15px; border-radius: 8px; border: 1px solid #ddd; box-sizing: border-box;">
                <button type="submit" class="btn-checkout">Complete Order</button>
            </form>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.qty++;
            } else {
                cart.push({ id: id, name: name, price: price, qty: 1 });
            }
            updateCartUI();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function updateCartUI() {
            const container = document.getElementById('cart-items');
            const totalSpan = document.getElementById('total-amount');
            const inputField = document.getElementById('cart-data-input');
            
            if (cart.length === 0) {
                container.innerHTML = '<p style="color: #999; text-align: center;">Cart is empty</p>';
                totalSpan.innerText = '0.00';
                inputField.value = "";
                return;
            }

            let html = '';
            let total = 0;
            cart.forEach((item, index) => {
                html += `
                    <div class="cart-item">
                        <span><strong>${item.qty}x</strong> ${item.name} 
                            <button class="btn-remove" onclick="removeFromCart(${index})">✕</button>
                        </span>
                        <span>Ksh ${(item.price * item.qty).toFixed(2)}</span>
                    </div>
                `;
                total += item.price * item.qty;
            });

            container.innerHTML = html;
            totalSpan.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2});
            inputField.value = JSON.stringify(cart); 
        }
    </script>
</body>
</html>
