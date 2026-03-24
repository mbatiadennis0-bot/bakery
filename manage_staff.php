<?php
session_start();
include 'db.php';

// 1. SECURITY: Only an Admin (like dayknow) can see this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$msg_type = "";

// 2. PROCESSING: Adding a New Staff Member
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'Staff'; // Strict logic: Admins must be created manually in DB

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $message = "Error: Username '$username' is already taken!";
        $msg_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) { 
            // Redirect to show success message and prevent re-submission on refresh
            header("Location: manage_staff.php?msg=Staff member added successfully!");
            exit();
        }
    }
}

// Catch redirect messages from delete_user.php or self-redirect
if(isset($_GET['msg'])) { $message = $_GET['msg']; $msg_type = "success"; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdf2e9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        
        .form-section { background: #fff9f4; padding: 25px; border-radius: 12px; border: 1px solid #f4a261; margin-bottom: 30px; }
        h2 { color: #264653; margin-top: 0; font-size: 22px; }
        
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        button { background: #e76f51; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.3s; }
        button:hover { background: #d65d41; transform: translateY(-2px); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #264653; color: white; padding: 15px; text-align: left; border-radius: 5px 5px 0 0; }
        td { padding: 15px; border-bottom: 1px solid #eee; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-admin { background: #f4a261; color: white; }
        .badge-staff { background: #2a9d8f; color: white; }
        
        .remove-link { color: #e76f51; text-decoration: none; font-weight: bold; font-size: 14px; }
        .remove-link:hover { text-decoration: underline; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .alert-success { background: #e9f5f2; color: #2a9d8f; border: 1px solid #2a9d8f; }
        .alert-error { background: #fff5f5; color: #e76f51; border: 1px solid #e76f51; }
    </style>
</head>
<body>

<div class="container">
    <?php include 'navbar.php'; ?>

    <div class="form-section">
        <h2>Add New Staff Member</h2>
        <?php if($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Enter Full Name or Username" required>
            <input type="password" name="password" placeholder="Create Default Password" required>
            <p style="font-size: 12px; color: #666; margin-bottom: 15px;">
                🔐 New accounts are restricted to the <strong>Staff</strong> role by default.
            </p>
            <button type="submit" name="add_user">Register & Add Staff</button>
        </form>
    </div>

    <h2>Existing System Users</h2>
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
            if ($result && $result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                    <td>
                        <span class="badge <?php echo ($row['role'] == 'Admin') ? 'badge-admin' : 'badge-staff'; ?>">
                            <?php echo $row['role']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($row['username'] === 'dayknow'): ?>
                            <span style="color: #999; font-size: 13px; font-style: italic;">Protected Master</span>
                        <?php else: ?>
                            <a href="delete_user.php?id=<?php echo $row['id']; ?>" 
                               class="remove-link" 
                               onclick="return confirm('WARNING: Are you sure you want to remove <?php echo $row['username']; ?> from the system?')">
                               Remove User
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php 
                endwhile; 
            else:
                echo "<tr><td colspan='3' style='text-align:center;'>No users found.</td></tr>";
            endif;
            ?>
        </tbody>
    </table>
</div>

</body>
</html>