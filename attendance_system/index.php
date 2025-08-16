<?php
require_once 'includes/session.php';

// Redirect based on login status
if (isLoggedIn()) {
    header('Location: /attendance_system/admin/dashboard.php');
} else {
    header('Location: /attendance_system/pages/login.php');
}
exit();
?>