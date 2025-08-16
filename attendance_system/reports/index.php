<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$pageTitle = 'Reports & Analytics - Attendance Management System';

// Get database connection
$database = new Database();
$conn = $database->connect();

$reportType = $_GET['report'] ?? 'summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$department = $_GET['department'] ?? '';
$personId = $_GET['person_id'] ?? '';

// Get data based on report type
$reportData = [];
$chartData = [];

// Get departments for filter
$stmt = $conn->query("SELECT DISTINCT department FROM people WHERE department IS NOT NULL AND department != '' AND status = 'active' ORDER BY department");
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get people for individual reports
$stmt = $conn->query("SELECT id, first_name, last_name, employee_id FROM people WHERE status = 'active' ORDER BY first_name, last_name");
$people = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($reportType === 'summary') {
    // Summary Report
    $sql = "
        SELECT 
            p.department,
            COUNT(DISTINCT p.id) as total_people,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as total_present,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as total_absent,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as total_late,
            COUNT(CASE WHEN a.status = 'half_day' THEN 1 END) as total_half_day,
            ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(a.id), 2) as attendance_percentage
        FROM people p
        LEFT JOIN attendance a ON p.id = a.person_id AND a.date BETWEEN ? AND ?
        WHERE p.status = 'active'
    ";
    
    $params = [$startDate, $endDate];
    
    if ($department) {
        $sql .= " AND p.department = ?";
        $params[] = $department;
    }
    
    $sql .= " GROUP BY p.department ORDER BY p.department";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chart data for summary
    $chartData = [
        'labels' => array_column($reportData, 'department'),
        'attendance' => array_column($reportData, 'attendance_percentage')
    ];
    
} elseif ($reportType === 'daily') {
    // Daily Attendance Report
    $sql = "
        SELECT 
            a.date,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
            COUNT(a.id) as total_marked,
            ROUND(COUNT(CASE WHEN a.status = 'present' THEN 1 END) * 100.0 / COUNT(a.id), 2) as attendance_percentage
        FROM attendance a
        JOIN people p ON a.person_id = p.id
        WHERE a.date BETWEEN ? AND ? AND p.status = 'active'
    ";
    
    $params = [$startDate, $endDate];
    
    if ($department) {
        $sql .= " AND p.department = ?";
        $params[] = $department;
    }
    
    $sql .= " GROUP BY a.date ORDER BY a.date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chart data for daily
    $chartData = [
        'labels' => array_reverse(array_column($reportData, 'date')),
        'attendance' => array_reverse(array_column($reportData, 'attendance_percentage'))
    ];
    
} elseif ($reportType === 'individual' && $personId) {
    // Individual Attendance Report
    $sql = "
        SELECT 
            a.*,
            p.first_name,
            p.last_name,
            p.employee_id,
            p.department
        FROM attendance a
        JOIN people p ON a.person_id = p.id
        WHERE a.person_id = ? AND a.date BETWEEN ? AND ?
        ORDER BY a.date DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$personId, $startDate, $endDate]);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get person details
    $stmt = $conn->prepare("SELECT * FROM people WHERE id = ?");
    $stmt->execute([$personId]);
    $personDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Chart data for individual
    $statusCounts = [];
    foreach ($reportData as $record) {
        $status = $record['status'];
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    
    $chartData = [
        'labels' => array_keys($statusCounts),
        'data' => array_values($statusCounts)
    ];
    
} elseif ($reportType === 'late_report') {
    // Late Arrivals Report
    $sql = "
        SELECT 
            a.date,
            p.employee_id,
            p.first_name,
            p.last_name,
            p.department,
            a.check_in_time,
            a.notes
        FROM attendance a
        JOIN people p ON a.person_id = p.id
        WHERE a.status = 'late' AND a.date BETWEEN ? AND ? AND p.status = 'active'
    ";
    
    $params = [$startDate, $endDate];
    
    if ($department) {
        $sql .= " AND p.department = ?";
        $params[] = $department;
    }
    
    $sql .= " ORDER BY a.date DESC, a.check_in_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Reports & Analytics</h1>
        <p class="page-subtitle">Generate comprehensive attendance reports and analytics.</p>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="report">Report Type</label>
                        <select id="report" name="report" onchange="togglePersonFilter()">
                            <option value="summary" <?php echo $reportType === 'summary' ? 'selected' : ''; ?>>Department Summary</option>
                            <option value="daily" <?php echo $reportType === 'daily' ? 'selected' : ''; ?>>Daily Attendance</option>
                            <option value="individual" <?php echo $reportType === 'individual' ? 'selected' : ''; ?>>Individual Report</option>
                            <option value="late_report" <?php echo $reportType === 'late_report' ? 'selected' : ''; ?>>Late Arrivals</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" 
                                        <?php echo $department === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group person-filter" style="margin-bottom: 0; <?php echo $reportType !== 'individual' ? 'display: none;' : ''; ?>">
                        <label for="person_id">Person</label>
                        <select id="person_id" name="person_id">
                            <option value="">Select Person</option>
                            <?php foreach ($people as $person): ?>
                                <option value="<?php echo $person['id']; ?>" 
                                        <?php echo $personId == $person['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name'] . ' (' . $person['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-outline" onclick="exportReport()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($reportType === 'summary'): ?>
        <!-- Department Summary Report -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Department Summary (<?php echo date('M j', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($reportData)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Total People</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Late</th>
                                        <th>Half Day</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['department'] ?: 'No Department'); ?></strong></td>
                                        <td><?php echo $row['total_people']; ?></td>
                                        <td><span class="badge badge-success"><?php echo $row['total_present']; ?></span></td>
                                        <td><span class="badge badge-error"><?php echo $row['total_absent']; ?></span></td>
                                        <td><span class="badge badge-warning"><?php echo $row['total_late']; ?></span></td>
                                        <td><span class="badge badge-info"><?php echo $row['total_half_day']; ?></span></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="background: var(--light-color); border-radius: 10px; height: 20px; width: 100px; overflow: hidden;">
                                                    <div style="background: var(--success-color); height: 100%; width: <?php echo $row['attendance_percentage']; ?>%; transition: width 0.3s ease;"></div>
                                                </div>
                                                <span><?php echo $row['attendance_percentage']; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h4>No Data Available</h4>
                            <p>No attendance data found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attendance Overview</h3>
                </div>
                <div class="card-body">
                    <canvas id="summaryChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>

    <?php elseif ($reportType === 'daily'): ?>
        <!-- Daily Attendance Report -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daily Attendance Report</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData)): ?>
                    <div style="margin-bottom: 2rem;">
                        <canvas id="dailyChart" width="800" height="300"></canvas>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Marked</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><strong><?php echo date('M j, Y', strtotime($row['date'])); ?></strong></td>
                                    <td><?php echo $row['total_marked']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $row['present_count']; ?></span></td>
                                    <td><span class="badge badge-error"><?php echo $row['absent_count']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $row['late_count']; ?></span></td>
                                    <td><?php echo $row['attendance_percentage']; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4>No Data Available</h4>
                        <p>No daily attendance data found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($reportType === 'individual' && $personId): ?>
        <!-- Individual Report -->
        <?php if (isset($personDetails)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div style="display: grid; grid-template-columns: auto 1fr auto; gap: 2rem; align-items: center;">
                    <div class="person-avatar" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($personDetails['first_name'], 0, 1) . substr($personDetails['last_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h2 style="margin: 0;"><?php echo htmlspecialchars($personDetails['first_name'] . ' ' . $personDetails['last_name']); ?></h2>
                        <p style="margin: 0.25rem 0; color: var(--text-light);">Employee ID: <?php echo htmlspecialchars($personDetails['employee_id']); ?></p>
                        <p style="margin: 0; color: var(--text-light);">Department: <?php echo htmlspecialchars($personDetails['department'] ?: 'N/A'); ?></p>
                    </div>
                    <div style="text-align: center;">
                        <canvas id="individualChart" width="200" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Attendance Records</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $row['status'] === 'present' ? 'success' : 
                                                ($row['status'] === 'late' ? 'warning' : 'error'); 
                                        ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['check_in_time'] ? date('g:i A', strtotime($row['check_in_time'])) : 'N/A'; ?></td>
                                    <td><?php echo $row['check_out_time'] ? date('g:i A', strtotime($row['check_out_time'])) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($row['notes'] ?: 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                        <h4>No Records Found</h4>
                        <p>No attendance records found for this person in the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($reportType === 'late_report'): ?>
        <!-- Late Arrivals Report -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Late Arrivals Report</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($reportData)): ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Check In Time</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['employee_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?: 'N/A'); ?></td>
                                    <td><span class="badge badge-warning"><?php echo date('g:i A', strtotime($row['check_in_time'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['notes'] ?: 'N/A'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h4>No Late Arrivals</h4>
                        <p>No late arrivals found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function togglePersonFilter() {
    const reportType = document.getElementById('report').value;
    const personFilter = document.querySelector('.person-filter');
    
    if (reportType === 'individual') {
        personFilter.style.display = 'block';
    } else {
        personFilter.style.display = 'none';
    }
}

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open(window.location.pathname + '?' + params.toString());
}

// Initialize charts based on report type
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($reportType === 'summary' && !empty($chartData['labels'])): ?>
    const summaryCtx = document.getElementById('summaryChart').getContext('2d');
    new Chart(summaryCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartData['labels']); ?>,
            datasets: [{
                label: 'Attendance %',
                data: <?php echo json_encode($chartData['attendance']); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderColor: 'rgb(79, 70, 229)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'daily' && !empty($chartData['labels'])): ?>
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(function($date) { return date('M j', strtotime($date)); }, $chartData['labels'])); ?>,
            datasets: [{
                label: 'Attendance %',
                data: <?php echo json_encode($chartData['attendance']); ?>,
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'individual' && !empty($chartData['labels'])): ?>
    const individualCtx = document.getElementById('individualChart').getContext('2d');
    new Chart(individualCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('ucfirst', $chartData['labels'])); ?>,
            datasets: [{
                data: <?php echo json_encode($chartData['data']); ?>,
                backgroundColor: [
                    'rgb(16, 185, 129)',
                    'rgb(239, 68, 68)',
                    'rgb(245, 158, 11)',
                    'rgb(6, 182, 212)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>