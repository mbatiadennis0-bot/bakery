<?php
include 'db.php'; 

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'Staff'; 

    $password_regex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/";

    if (empty($username) || empty($password)) {
        $message = "Error: All fields are required.";
        $message_type = "error";
    } 
    elseif (!preg_match($password_regex, $password)) {
        $message = "Error: Password must meet all complexity requirements.";
        $message_type = "error";
    }
    elseif ($password !== $confirm_password) {
        $message = "Error: Passwords do not match!";
        $message_type = "error";
    } 
    else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $message = "Error: Username '$username' is already taken!";
            $message_type = "error";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt->execute()) {
                // Redirect to login page with a success flag
                header("Location: login.php?msg=success");
                exit();
            } else {
                $message = "System error. Please try again later.";
                $message_type = "error";
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery - Register Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                        url('https://images.unsplash.com/photo-1608198093002-ad4e005484ec?auto=format&fit=crop&w=1600&q=80') 
                        center/cover no-repeat;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            width: 90%;
            max-width: 420px;
            text-align: center;
        }

        h2 { color: #c96b2c; margin-bottom: 25px; font-size: 28px; font-weight: 600; }
        
        .form-group { 
            margin-bottom: 18px; 
            text-align: left; 
            position: relative; 
        }

        label { display: block; margin-bottom: 8px; font-weight: 500; color: #264653; font-size: 14px; }

        input { 
            width: 100%; 
            padding: 12px 60px 12px 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 15px; 
            transition: 0.3s; 
            display: block;
        }

        input:focus { border-color: #f4a261; outline: none; box-shadow: 0 0 5px rgba(244, 162, 97, 0.3); }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500; line-height: 1.4; text-align: left; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        
        /* Fixed Toggle Styling */
        .toggle-btn {
            position: absolute;
            right: 15px;
            /* This centers the text vertically relative to the input height */
            top: 42px; 
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            color: #e76f51;
            text-transform: uppercase;
            user-select: none;
            letter-spacing: 0.5px;
        }

        .pwd-requirements { font-size: 10px; color: #666; margin-top: 5px; list-style: none; padding-left: 2px; }

        button.submit-btn {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #f4a261, #e76f51);
            border: none; border-radius: 8px; color: white; font-size: 17px; font-weight: 600; cursor: pointer; transition: 0.3s;
            margin-top: 10px;
        }
        button.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(231, 111, 81, 0.4); }
        
        .login-link { margin-top: 25px; font-size: 14px; color: #666; }
        .login-link a { color: #e76f51; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="register-container">
    <form method="POST" autocomplete="off">
        <h2>Register Staff</h2>
        
        <?php if($message != ""): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password" placeholder="Create password" required>
            <span class="toggle-btn" id="toggle-password" onclick="togglePassword('password', 'toggle-password')">Show</span>
            <ul class="pwd-requirements">
                <li>• Min 6 characters (A-z, 0-9, @#$!)</li>
            </ul>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required>
            <span class="toggle-btn" id="toggle-confirm" onclick="togglePassword('confirm_password', 'toggle-confirm')">Show</span>
        </div>

        <button type="submit" class="submit-btn">Create Staff Account</button>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>

<script>
    function togglePassword(fieldId, btnId) {
        const field = document.getElementById(fieldId);
        const btn = document.getElementById(btnId);
        
        if (field.type === 'password') {
            field.type = 'text';
            btn.textContent = 'Hide';
        } else {
            field.type = 'password';
            btn.textContent = 'Show';
        }
    }
</script>

</body>
</html>