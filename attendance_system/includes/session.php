<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /attendance_system/pages/login.php');
        exit();
    }
}

function login($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
}

function logout() {
    session_destroy();
    header('Location: /attendance_system/pages/login.php');
    exit();
}

function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function requireRole($roles) {
    requireLogin();
    if (!in_array(getUserRole(), $roles)) {
        header('Location: /attendance_system/pages/unauthorized.php');
        exit();
    }
}
?>