<?php
// 1. ALWAYS start session and include DB first
session_start();
include 'db.php'; 

// 2. Security Check: Redirect if not logged in
if (!isset($_SESSION['username'])) { 
    header("Location: login.php"); 
    exit(); 
}

// 3. Define all variables AFTER inclusion and session start
date_default_timezone_set('Africa/Nairobi');
$user = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0; 
$user_role = $_SESSION['role'] ?? 'staff'; 
$message = "";

// 4. Check current status 
$status_query = $conn->prepare("SELECT * FROM attendance WHERE username = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1");
$status_query->bind_param("s", $user);
$status_query->execute();
$active_session = $status_query->get_result()->fetch_assoc();
$current_status = ($active_session) ? 'In' : 'Out';

// 5. Handle Clock In/Out Logic
if (isset($_POST['action'])) {
    $now = date('Y-m-d H:i:s');
    
    if ($_POST['action'] == 'clock_in' && $current_status == 'Out') {
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, username, clock_in, status) VALUES (?, ?, ?, 'In')");
        $stmt->bind_param("iss", $user_id, $user, $now);
        
        if($stmt->execute()) {
            $message = "✅ Shift started at " . date('h:i A');
        }
    } 
    elseif ($_POST['action'] == 'clock_out' && $current_status == 'In') {
        $session_id = $active_session['id'];
        $stmt = $conn->prepare("UPDATE attendance SET clock_out = ?, status = 'Out' WHERE id = ?");
        $stmt->bind_param("si", $now, $session_id);
        
        if($stmt->execute()) {
            $message = "👋 Shift ended at " . date('h:i A');
        }
    }
    header("Location: attendance.php?msg=" . urlencode($message));
    exit();
}

if(isset($_GET['msg'])) { $message = $_GET['msg']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdf2e9; padding: 20px; text-align: center; margin: 0; }
        .clock-card { 
            background: white; padding: 40px; border-radius: 20px; 
            display: inline-block; box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
            min-width: 380px; border-top: 6px solid #f4a261; margin-top: 20px;
        }
        .btn { padding: 16px; border: none; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; font-size: 18px; width: 100%; margin-top: 10px; transition: 0.3s; }
        .btn-in { background: #2a9d8f; }
        .btn-in:hover { background: #21867a; }
        .btn-out { background: #e76f51; }
        .btn-out:hover { background: #cf5d44; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; background: <?= ($current_status == 'In') ? '#e9f5f2' : '#fef4f2' ?>; color: <?= ($current_status == 'In') ? '#2a9d8f' : '#e76f51' ?>; }
        .history-table { max-width: 900px; margin: 30px auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #264653; color: white; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #444; }
        .hours-tag { background: #f4a261; color: white; padding: 2px 8px; border-radius: 5px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="clock-card">
        <div class="status-badge"><?= strtoupper($current_status) ?> NOW</div>
        <h2 style="color:#264653; margin: 0;">Shift Manager</h2>
        <p style="color:#666;">Account: <strong><?= htmlspecialchars($user) ?></strong> (<?= ucfirst($user_role) ?>)</p>
        
        <form method="POST">
            <?php if($current_status == 'Out'): ?>
                <button type="submit" name="action" value="clock_in" class="btn btn-in">Start Shift 🕒</button>
            <?php else: ?>
                <button type="submit" name="action" value="clock_out" class="btn btn-out">End Shift 👋</button>
            <?php endif; ?>
        </form>

        <?php if($message): ?>
            <div style="margin-top:20px; color:#2a9d8f; font-weight:600;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>

    <div class="history-table">
        <h4 style="margin: 20px; color: #264653;">
            <?= ($user_role == 'admin') ? "📋 Company Attendance Overview" : "Your Recent Shifts" ?>
        </h4>
        <table>
            <thead>
                <tr>
                    <?php if($user_role == 'admin'): ?><th>Staff</th><?php endif; ?>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($user_role == 'admin') {
                    $sql = "SELECT *, TIMESTAMPDIFF(MINUTE, clock_in, clock_out)/60 AS hours_worked FROM attendance ORDER BY id DESC LIMIT 15";
                } else {
                    $sql = "SELECT *, TIMESTAMPDIFF(MINUTE, clock_in, clock_out)/60 AS hours_worked FROM attendance WHERE username = '$user' ORDER BY id DESC LIMIT 10";
                }
                
                $recent = $conn->query($sql);
                if ($recent && $recent->num_rows > 0):
                    while($r = $recent->fetch_assoc()):
                        $duration = ($r['hours_worked']) ? round($r['hours_worked'], 2) . " hrs" : "---";
                ?>
                <tr>
                    <?php if($user_role == 'admin'): ?><td><strong><?= htmlspecialchars($r['username']) ?></strong></td><?php endif; ?>
                    <td><?= date('M d, Y', strtotime($r['clock_in'])) ?></td>
                    <td><?= date('h:i A', strtotime($r['clock_in'])) ?></td>
                    <td><?= ($r['clock_out']) ? date('h:i A', strtotime($r['clock_out'])) : '<span style="color:#2a9d8f; font-weight:bold;">Active</span>' ?></td>
                    <td><span class="hours-tag"><?= $duration ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" style="padding:20px; color:#999;">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>