<?php 
session_start();
include 'db.php'; 

// 1. ACCESS CONTROL: Only Admins can manage users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// 2. ADMIN COUNT: Initial check to set the limit
$admin_count_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Admin'");
$admin_data = $admin_count_query->fetch_assoc();
$current_admins = $admin_data['total'];

$msg = ""; 

// 3. PROCESSING: Add New User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role']; 

    // STRICT PHP CHECK: Even if they hack the HTML, this stops the insert
    if ($role === 'Admin' && $current_admins >= 3) {
        $msg = "<div class='alert error'>⛔ STRICT DENIAL: Maximum of 3 Admins reached.</div>";
    } else {
        // Use Prepared Statement to check if user exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $msg = "<div class='alert error'>⚠️ Username already exists!</div>";
        } else {
            // Prepared Statement for Insertion
            $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $password, $role);
            
            if ($insert->execute()) {
                $msg = "<div class='alert success'>✅ User '$username' added successfully!</div>";
                
                // Refresh Admin count for the display
                $admin_count_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'Admin'");
                $admin_data = $admin_count_query->fetch_assoc();
                $current_admins = $admin_data['total'];
            } else {
                $msg = "<div class='alert error'>❌ Error adding user.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Management - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdf2e9; padding: 20px; color: #264653; }
        .card { max-width: 900px; margin: auto; background: white; padding: 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .registration-form { background: #fff9f4; padding: 25px; border-radius: 12px; border: 1px solid #f4a261; margin: 25px 0; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        
        .btn-submit { background: #e76f51; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-submit:hover { background: #d65d41; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #264653; color: white; padding: 15px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-admin { background: #fef3c7; color: #92400e; border: 1px solid #f4a261; }
        .badge-staff { background: #e9ecef; color: #495057; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .success { background: #e9f5f2; color: #2a9d8f; border: 1px solid #2a9d8f; }
        .error { background: #fff5f5; color: #e76f51; border: 1px solid #e76f51; }
        
        .status-text { font-size: 14px; font-weight: 600; color: <?php echo ($current_admins >= 3) ? '#e76f51' : '#2a9d8f'; ?>; }
    </style>
</head>
<body>

<div class="card">
    <?php include 'navbar.php'; ?>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <h2>Staff Management</h2>
        <span class="status-text">Admin Capacity: <?php echo $current_admins; ?>/3</span>
    </div>

    <div class="registration-form">
        <?php echo $msg; ?>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="username" placeholder="e.g. John Doe" required>
            
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required>
            
            <label>Assigned Role:</label>
            <select name="role">
                <option value="Staff">Staff (Standard Access)</option>
                <?php if ($current_admins < 3): ?>
                    <option value="Admin">Admin (Full Control)</option>
                <?php endif; ?>
            </select>
            
            <?php if ($current_admins >= 3): ?>
                <p style="color: #e76f51; font-size: 12px; margin-top: -5px;">⚠️ The Admin limit has been reached. New users will be Staff.</p>
            <?php endif; ?>

            <button type="submit" name="add_user" class="btn-submit">Register New User</button>
        </form>
    </div>

    <h2>Existing Users</h2>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT id, username, role FROM users ORDER BY role ASC, username ASC");
            while($row = $result->fetch_assoc()):
                $roleClass = ($row['role'] === 'Admin') ? 'badge-admin' : 'badge-staff';
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td><span class="badge <?php echo $roleClass; ?>"><?php echo $row['role']; ?></span></td>
                    <td>
                        <?php if($row['username'] !== 'dayknow'): ?>
                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" 
                               style="color:#e76f51; text-decoration:none; font-weight:bold;" 
                               onclick="return confirm('Delete this user account?')">Remove</a>
                        <?php else: ?>
                            <span style="color:gray; font-size: 13px; font-style: italic;">Protected (Master)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>