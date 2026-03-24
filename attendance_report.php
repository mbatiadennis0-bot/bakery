<?php 
// 1. Session and Security Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php'; 

// Only Admin (like dayknow) can see this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Set Timezone for Kenya
date_default_timezone_set('Africa/Nairobi');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Attendance - Bakery System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #fdf2e9; padding: 40px 20px; display: flex; justify-content: center; }
        .card { width: 100%; max-width: 1000px; background: white; padding: 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h2 { color: #264653; border-left: 5px solid #f4a261; padding-left: 15px; }
        
        .btn-back { text-decoration: none; background: #264653; color: white; padding: 8px 15px; border-radius: 8px; font-size: 14px; transition: 0.3s; }
        .btn-back:hover { background: #f4a261; }

        .search-box { width: 100%; padding: 14px; margin-bottom: 25px; border: 2px solid #f4a261; border-radius: 10px; outline: none; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #264653; color: white; padding: 18px 15px; text-align: left; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #444; }
        
        .status-in { color: #2a9d8f; font-weight: bold; }
        .status-out { color: #e76f51; font-weight: bold; }
        .still-working { color: #999; font-style: italic; font-size: 13px; }
    </style>
</head>
<body>

<div class="card">
    <div class="header-flex">
        <h2>Staff Attendance Logs</h2>
        <a href="dashboard.php" class="btn-back">← Dashboard</a>
    </div>
    
    <input type="text" id="attendanceSearch" onkeyup="searchAttendance()" placeholder="Search by staff name (e.g., Raquel)..." class="search-box">

    <div class="table-container">
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query matches the table created in Step 1
                $logs = $conn->query("SELECT * FROM attendance ORDER BY clock_in DESC");
                
                if ($logs && $logs->num_rows > 0):
                    while($row = $logs->fetch_assoc()):
                        // Format the display for Clock Out
                        $out_display = ($row['clock_out']) 
                            ? date('h:i A', strtotime($row['clock_out'])) 
                            : "<span class='still-working'>Still Working</span>";
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                        <td class="status-in"><?php echo date('h:i A', strtotime($row['clock_in'])); ?></td>
                        <td class="status-out"><?php echo $out_display; ?></td>
                        <td><?php echo date('d M, Y', strtotime($row['clock_in'])); ?></td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr><td colspan="4" style="text-align:center; padding: 30px; color: #777;">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function searchAttendance() {
    let input = document.getElementById("attendanceSearch").value.toUpperCase();
    let rows = document.getElementById("attendanceTable").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let text = rows[i].innerText.toUpperCase();
        rows[i].style.display = text.includes(input) ? "" : "none";
    }
}
</script>

</body>
</html>