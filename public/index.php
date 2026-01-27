<?php
// public/index.php
session_start();
if (!empty($_SESSION['role'])) {
    header("Location: /capstone-repo/{$_SESSION['role']}/dashboard.php");
    exit;
}
header("Location: /capstone-repo/public/login.php");
exit;