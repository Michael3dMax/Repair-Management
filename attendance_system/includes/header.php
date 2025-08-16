<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Attendance Management System'; ?></title>
    <link rel="stylesheet" href="/attendance_system/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-clock"></i>
                <span>AttendanceMS</span>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/attendance_system/admin/dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/attendance_system/admin/people.php" class="nav-link">
                        <i class="fas fa-users"></i> People
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/attendance_system/admin/attendance.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/attendance_system/reports/index.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                    </a>
                    <div class="dropdown-menu">
                        <a href="/attendance_system/admin/profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i> Profile
                        </a>
                        <a href="/attendance_system/admin/settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/attendance_system/pages/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
            
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>