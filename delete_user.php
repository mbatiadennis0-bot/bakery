<?php
session_start();
include 'db.php';

// 1. Security Check: Only an Admin can access this file
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// 2. Validate the ID from the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // 3. Use a Prepared Statement to find the user first
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 4. MASTER PROTECTION: Block deletion of the main admin 'dayknow'
        if ($user['username'] === 'dayknow') {
            echo "<script>
                    alert('CRITICAL ERROR: The Master Admin account is protected and cannot be deleted!'); 
                    window.location='manage_staff.php';
                  </script>";
            exit();
        } else {
            // 5. Execute Delete using Prepared Statement
            $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->bind_param("i", $id);
            
            if ($del_stmt->execute()) {
                // Redirect back with a success message
                header("Location: manage_staff.php?msg=User removed successfully"); 
                exit();
            } else {
                echo "Error deleting record: " . $conn->error;
            }
            $del_stmt->close();
        }
    } else {
        header("Location: manage_staff.php?error=User not found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: manage_staff.php");
    exit();
}
?>