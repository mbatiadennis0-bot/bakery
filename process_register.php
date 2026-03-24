<?php
// Include database connection
include 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. SANITIZATION: Clean up whitespace and prevent basic injection
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // FORCE ROLE: Always hardcode this to 'Staff' for public registration
    // Admin roles should only be created manually in the database for security.
    $role = 'Staff'; 

    if (empty($username) || empty($password)) {
        echo "<script>alert('Please fill in all fields.'); window.history.back();</script>";
        exit();
    }

    // 2. CHECK DUPLICATES: Ensure the username isn't taken
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('Error: This username is already taken!'); window.history.back();</script>";
    } else {
        // 3. SECURE HASHING: Create a secure hash of the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 4. INSERT: Save the new staff member
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            // Success: Force a redirect to login to clear POST data
            echo "<script>
                    alert('Registration Successful! You can now log in.');
                    window.location.href = 'login.php'; 
                  </script>";
        } else {
            echo "<script>alert('System Error: Could not complete registration.'); window.history.back();</script>";
        }
        $stmt->close();
    }
    $check_stmt->close();
} else {
    // If someone tries to access this file directly, send them to the register page
    header("Location: register.php");
    exit();
}
?>