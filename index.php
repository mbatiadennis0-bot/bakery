<?php
session_start();
require_once 'app_url.php';

if (isset($_SESSION['username'])) {
    header('Location: ' . app_url('dashboard.php'));
    exit();
}

header('Location: ' . app_url('login.php'));
exit();
?>
