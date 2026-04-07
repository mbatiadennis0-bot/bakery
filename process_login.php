<?php
// 1. ABSOLUTE TOP: Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Include database connection
include 'db.php';
require_once 'app_url.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 3. Prepare statement to find the user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 4. Verify password
        if (password_verify($password, $user['password'])) {
            
            // Security: Prevent session fixation
            session_regenerate_id(true);

            // 5. Store user details in the session
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; 
            
            // Redirect to the dashboard
            header("Location: " . app_url('dashboard.php'));
            exit();
        } else {
            // REDIRECT with error code for "Invalid Password"
            header("Location: " . app_url('login.php?error=wrongpassword'));
            exit();
        }
    } else {
        // REDIRECT with error code for "User Not Found"
        header("Location: " . app_url('login.php?error=notfound'));
        exit();
    }
} else {
    header("Location: " . app_url('login.php'));
    exit();
}
?>
