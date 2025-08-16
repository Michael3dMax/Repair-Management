<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$pageTitle = 'Dashboard - Attendance Management System';

// Get database connection
$database = new Database();
$conn = $database->connect();

// Get statistics
$stats = [];

// Total people
$stmt = $conn->query("SELECT COUNT(*) as total FROM people WHERE status = 'active'");
$stats['total_people'] = $stmt->fetch()['total'];

// Today's attendance
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
        COUNT(*) as total_marked
    FROM attendance 
    WHERE date = ?
");
$stmt->execute([$today]);
$attendance_stats = $stmt->fetch();

$stats['present_today'] = $attendance_stats['present'];
$stats['absent_today'] = $attendance_stats['absent'];
$stats['late_today'] = $attendance_stats['late'];
$stats['total_marked'] = $attendance_stats['total_marked'];

// Recent attendance records
$stmt = $conn->prepare("
    SELECT a.*, p.first_name, p.last_name, p.employee_id, p.department
    FROM attendance a
    JOIN people p ON a.person_id = p.id
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_attendance = $stmt->fetchAll();

// Department wise attendance for today
$stmt = $conn->prepare("
    SELECT 
        p.department,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late
    FROM people p
    LEFT JOIN attendance a ON p.id = a.person_id AND a.date = ?
    WHERE p.status = 'active' AND p.department IS NOT NULL
    GROUP BY p.department
");
$stmt->execute([$today]);
$department_stats = $stmt->fetchAll();

// If AJAX request, return JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'attendance_count' => true,
        'total_people' => $stats['total_people'],
        'present_today' => $stats['present_today'],
        'absent_today' => $stats['absent_today'],
        'late_today' => $stats['late_today']
    ]);
    exit;
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?php echo $_SESSION['username']; ?>! Here's what's happening today.</p>
        <div class="page-actions">
            <a href="attendance.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Mark Attendance
            </a>
            <a href="people.php" class="btn btn-outline">
                <i class="fas fa-users"></i> Manage People
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card primary">
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="number"><?php echo $stats['total_people']; ?></div>
            <div class="label">Total People</div>
        </div>

        <div class="stat-card success">
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="number"><?php echo $stats['present_today']; ?></div>
            <div class="label">Present Today</div>
        </div>

        <div class="stat-card error">
            <div class="icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="number"><?php echo $stats['absent_today']; ?></div>
            <div class="label">Absent Today</div>
        </div>

        <div class="stat-card warning">
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="number"><?php echo $stats['late_today']; ?></div>
            <div class="label">Late Today</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Recent Attendance -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Attendance</h3>
                <a href="attendance.php" class="btn btn-small btn-outline">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_attendance)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_attendance as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $record['first_name'] . ' ' . $record['last_name']; ?></strong><br>
                                        <small class="text-muted"><?php echo $record['employee_id']; ?></small>
                                    </td>
                                    <td><?php echo $record['department'] ?: 'N/A'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo $record['check_in_time'] ? date('g:i A', strtotime($record['check_in_time'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $record['status'] === 'present' ? 'success' : 
                                                ($record['status'] === 'late' ? 'warning' : 'error'); 
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p>No attendance records found for today.</p>
                        <a href="attendance.php" class="btn btn-primary">Mark Attendance</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Department Statistics -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Department Overview</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($department_stats)): ?>
                    <?php foreach ($department_stats as $dept): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong><?php echo $dept['department']; ?></strong>
                            <span class="text-muted"><?php echo ($dept['present'] + $dept['absent'] + $dept['late']); ?> total</span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; font-size: 0.875rem;">
                            <span class="badge badge-success"><?php echo $dept['present']; ?> Present</span>
                            <span class="badge badge-error"><?php echo $dept['absent']; ?> Absent</span>
                            <span class="badge badge-warning"><?php echo $dept['late']; ?> Late</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center p-3">
                        <i class="fas fa-building fa-2x text-muted mb-2"></i>
                        <p>No department data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="people.php?action=add" class="btn btn-outline" style="padding: 1.5rem; text-align: center; text-decoration: none;">
                    <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                    Add New Person
                </a>
                <a href="attendance.php" class="btn btn-outline" style="padding: 1.5rem; text-align: center; text-decoration: none;">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i><br>
                    Mark Attendance
                </a>
                <a href="../reports/index.php" class="btn btn-outline" style="padding: 1.5rem; text-align: center; text-decoration: none;">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                    View Reports
                </a>
                <a href="settings.php" class="btn btn-outline" style="padding: 1.5rem; text-align: center; text-decoration: none;">
                    <i class="fas fa-cog fa-2x mb-2"></i><br>
                    Settings
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.text-muted {
    color: var(--text-light) !important;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 480px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    div[style*="grid-template-columns: repeat(2, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>