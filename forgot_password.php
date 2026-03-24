<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery System - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(45, 24, 13, 0.6), rgba(45, 24, 13, 0.6)),
                        url('https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1600&q=80') 
                        center/cover no-repeat;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 50px 40px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            width: 90%;
            max-width: 420px;
            text-align: center;
        }

        h2 { margin-bottom: 15px; font-size: 24px; color: #264653; font-weight: 600; }
        p { color: #666; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #264653; font-size: 13px; }

        input { 
            width: 100%; 
            padding: 14px 20px; 
            border: none; 
            background: #e9f0fe; 
            border-radius: 12px; 
            font-size: 15px; 
            transition: 0.3s;
        }

        input:focus { outline: none; background: #fff; box-shadow: 0 0 0 2px #f4a261; }
        
        .btn-primary {
            width: 100%; padding: 15px; background: #f4a261;
            border: none; border-radius: 12px; color: white; 
            font-size: 16px; font-weight: 600; cursor: pointer; 
            transition: 0.3s; margin-top: 10px;
        }

        .btn-primary:hover { background: #e76f51; transform: translateY(-2px); }
        
        .back-link { margin-top: 25px; display: block; color: #e76f51; text-decoration: none; font-weight: 600; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="auth-container">
    <form action="reset_action.php" method="POST">
        <h2>Reset Password</h2>
        <p>Enter your username and your new desired password to regain access.</p>

        <div class="form-group">
            <label>Confirm Username</label>
            <input type="text" name="username" placeholder="Type your username" required>
        </div>

        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-primary">Update Password</button>

        <a href="login.php" class="back-link">← Back to Login</a>
    </form>
</div>

</body>
</html>