<?php 
session_start();
include 'db.php'; 

if (!isset($_SESSION['username'])) { 
    header("Location: login.php"); 
    exit(); 
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_SESSION['username'];
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Fetch current hashed password from DB using Prepared Statement
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    // 2. Validation
    // Use password_verify to check against the hash in the DB
    if (!password_verify($old_pass, $user['password'])) {
        $message = "<p style='color: #e76f51; text-align:center; font-weight:600;'>Current password is incorrect!</p>";
    } elseif ($new_pass !== $confirm_pass) {
        $message = "<p style='color: #e76f51; text-align:center; font-weight:600;'>New passwords do not match!</p>";
    } elseif (strlen($new_pass) < 4) {
        $message = "<p style='color: #e76f51; text-align:center; font-weight:600;'>New password is too short!</p>";
    } else {
        // 3. Update Password using hashing
        $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $update_stmt->bind_param("ss", $hashed_new_pass, $username);
        
        if ($update_stmt->execute()) {
            $message = "<p style='color: #2a9d8f; text-align:center; font-weight:600;'>Password updated successfully!</p>";
        } else {
            $message = "<p style='color: #e76f51; text-align:center; font-weight:600;'>Error updating password.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #264653; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .card { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); 
            width: 100%;
            max-width: 400px; 
        }
        h2 { color: #264653; margin-bottom: 25px; text-align: center; border-bottom: 2px solid #f4a261; padding-bottom: 10px; }
        input { 
            width: 100%; 
            padding: 14px; 
            margin-bottom: 15px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            box-sizing: border-box; 
            outline: none;
        }
        input:focus { border-color: #f4a261; }
        button { 
            width: 100%; 
            background: linear-gradient(135deg, #f4a261, #e76f51); 
            color: white; 
            border: none; 
            padding: 14px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.3s;
        }
        button:hover { opacity: 0.9; transform: translateY(-2px); }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #264653; text-decoration: none; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Security Settings</h2>
        <?php echo $message; ?>
        <form method="POST">
            <input type="password" name="old_password" placeholder="Current Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit">Update Password</button>
        </form>
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</body>
</html>