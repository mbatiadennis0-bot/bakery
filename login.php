<?php
session_start();
require_once 'app_url.php';
// Prevent the browser from storing a "snapshot" of the page in its cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=80') 
                        center/cover no-repeat;
        }

        .login-card {
            background: #ffffff;
            padding: 50px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 95%;
            max-width: 420px;
            text-align: center;
        }

        h2 { 
            color: #d37a4c; 
            margin-bottom: 25px; 
            font-size: 26px; 
            font-weight: 600; 
        }

        .message-box {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            line-height: 1.4;
        }

        .error-notfound { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .error-password { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .success-box { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        .form-group { margin-bottom: 22px; text-align: left; }
        
        label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: 600; 
            color: #264653; 
            font-size: 14px; 
        }

        input { 
            width: 100%; 
            padding: 14px 18px; 
            border: 1px solid #ddd; 
            background: #f9f9f9; 
            border-radius: 8px; 
            font-size: 15px; 
            color: #333;
            transition: 0.3s;
        }

        input:focus { 
            outline: none;
            border-color: #f4a261;
            background: #fff; 
            box-shadow: 0 0 5px rgba(244, 162, 97, 0.2);
        }
        
        .btn-login {
            width: 100%; 
            padding: 16px; 
            background: #f18d5f; 
            border: none; 
            border-radius: 8px; 
            color: white; 
            font-size: 17px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover { 
            background: #e76f51; 
            box-shadow: 0 5px 15px rgba(241, 141, 95, 0.4);
        }
        
        .footer-links { 
            margin-top: 35px; 
            font-size: 13px; 
            font-weight: 600;
        }
        
        .footer-links a { color: #d37a4c; text-decoration: none; }
        .footer-links a:hover { text-decoration: underline; }
        .divider { color: #ccc; margin: 0 8px; font-weight: 300; }
    </style>
</head>
<body>

<div class="login-card">
    <form action="<?= htmlspecialchars(app_url('process_login.php')) ?>" method="POST" autocomplete="off">
        <h2>Bakery Login</h2>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="message-box success-box">
                Account created successfully! You can now log in.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'notfound'): ?>
            <div class="message-box error-notfound">
                User not found. Please <a href="<?= htmlspecialchars(app_url('register.php')) ?>" style="color: #721c24; text-decoration: underline;">register</a>.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'wrongpassword'): ?>
            <div class="message-box error-password">
                Invalid password. Please try again or <a href="<?= htmlspecialchars(app_url('forgot_password.php')) ?>" style="color: #856404; text-decoration: underline;">reset it</a>.
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required autocomplete="none" value="">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••" required autocomplete="new-password" value="">
        </div>

        <button type="submit" class="btn-login">Login</button>

        <div class="footer-links">
            <a href="<?= htmlspecialchars(app_url('register.php')) ?>">Create Account</a>
            <span class="divider">|</span>
            <a href="<?= htmlspecialchars(app_url('forgot_password.php')) ?>">Forgot Password?</a>
        </div>
    </form>
</div>

</body>
</html>
