<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$pageTitle = 'Attendance - Attendance Management System';

// Get database connection
$database = new Database();
$conn = $database->connect();

$action = $_GET['action'] ?? 'mark';
$date = $_GET['date'] ?? date('Y-m-d');
$department = $_GET['department'] ?? '';
$message = '';
$messageType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'mark' && isset($_POST['attendance'])) {
        $attendanceData = $_POST['attendance'];
        $selectedDate = $_POST['date'];
        
        try {
            $conn->beginTransaction();
            
            foreach ($attendanceData as $personId => $data) {
                if (isset($data['mark'])) {
                    $status = $data['status'] ?? 'present';
                    $checkIn = $data['check_in'] ?? null;
                    $checkOut = $data['check_out'] ?? null;
                    $notes = $data['notes'] ?? '';
                    
                    // Check if attendance already exists
                    $stmt = $conn->prepare("SELECT id FROM attendance WHERE person_id = ? AND date = ?");
                    $stmt->execute([$personId, $selectedDate]);
                    $existingAttendance = $stmt->fetch();
                    
                    if ($existingAttendance) {
                        // Update existing record
                        $stmt = $conn->prepare("
                            UPDATE attendance 
                            SET status = ?, check_in_time = ?, check_out_time = ?, notes = ?, created_by = ?
                            WHERE person_id = ? AND date = ?
                        ");
                        $stmt->execute([$status, $checkIn, $checkOut, $notes, $_SESSION['user_id'], $personId, $selectedDate]);
                    } else {
                        // Insert new record
                        $stmt = $conn->prepare("
                            INSERT INTO attendance (person_id, date, status, check_in_time, check_out_time, notes, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$personId, $selectedDate, $status, $checkIn, $checkOut, $notes, $_SESSION['user_id']]);
                    }
                }
            }
            
            $conn->commit();
            $message = 'Attendance marked successfully.';
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = 'Error marking attendance: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    if ($action === 'quick' && isset($_POST['person_id'])) {
        $personId = $_POST['person_id'];
        $status = $_POST['status'];
        $currentTime = date('H:i:s');
        $today = date('Y-m-d');
        
        try {
            // Check if attendance already exists for today
            $stmt = $conn->prepare("SELECT id FROM attendance WHERE person_id = ? AND date = ?");
            $stmt->execute([$personId, $today]);
            $existingAttendance = $stmt->fetch();
            
            if ($existingAttendance) {
                $stmt = $conn->prepare("
                    UPDATE attendance 
                    SET status = ?, check_in_time = ?, created_by = ?
                    WHERE person_id = ? AND date = ?
                ");
                $stmt->execute([$status, $currentTime, $_SESSION['user_id'], $personId, $today]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO attendance (person_id, date, status, check_in_time, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$personId, $today, $status, $currentTime, $_SESSION['user_id']]);
            }
            
            // Get person name for response
            $stmt = $conn->prepare("SELECT first_name, last_name FROM people WHERE id = ?");
            $stmt->execute([$personId]);
            $person = $stmt->fetch();
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Attendance marked for {$person['first_name']} {$person['last_name']}"
                ]);
                exit;
            }
            
            $message = "Attendance marked for {$person['first_name']} {$person['last_name']}";
        } catch (PDOException $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Error marking attendance: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $message = 'Error marking attendance: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get people for attendance marking
if ($action === 'mark') {
    $sql = "SELECT p.*, a.status, a.check_in_time, a.check_out_time, a.notes 
            FROM people p 
            LEFT JOIN attendance a ON p.id = a.person_id AND a.date = ?
            WHERE p.status = 'active'";
    $params = [$date];
    
    if ($department) {
        $sql .= " AND p.department = ?";
        $params[] = $department;
    }
    
    $sql .= " ORDER BY p.department, p.first_name, p.last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get departments for filter
    $stmt = $conn->query("SELECT DISTINCT department FROM people WHERE department IS NOT NULL AND department != '' AND status = 'active' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else if ($action === 'quick') {
    // Get people for quick attendance
    $stmt = $conn->prepare("
        SELECT p.*, a.status as attendance_status 
        FROM people p 
        LEFT JOIN attendance a ON p.id = a.person_id AND a.date = ?
        WHERE p.status = 'active'
        ORDER BY p.first_name, p.last_name
    ");
    $stmt->execute([date('Y-m-d')]);
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Set session message
if ($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $messageType;
}

require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Attendance Management</h1>
        <p class="page-subtitle">Mark and manage attendance for students and employees.</p>
        <div class="page-actions">
            <a href="?action=mark" class="btn <?php echo $action === 'mark' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-calendar-check"></i> Bulk Mark
            </a>
            <a href="?action=quick" class="btn <?php echo $action === 'quick' ? 'btn-primary' : 'btn-outline'; ?>">
                <i class="fas fa-bolt"></i> Quick Mark
            </a>
            <a href="../reports/index.php" class="btn btn-outline">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
        </div>
    </div>

    <?php if ($action === 'mark'): ?>
        <!-- Bulk Attendance Marking -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="action" value="mark">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo $date; ?>" max="<?php echo date('Y-m-d'); ?>">
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
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" id="attendanceForm">
            <input type="hidden" name="date" value="<?php echo $date; ?>">
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attendance for <?php echo date('F j, Y', strtotime($date)); ?></h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-small btn-success" onclick="markAllPresent()">
                            <i class="fas fa-check"></i> Mark All Present
                        </button>
                        <button type="button" class="btn btn-small btn-outline" onclick="clearAll()">
                            <i class="fas fa-times"></i> Clear All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($people)): ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mark</th>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($people as $person): ?>
                                    <tr>
                                        <td>
                                            <label class="checkbox">
                                                <input type="checkbox" name="attendance[<?php echo $person['id']; ?>][mark]" 
                                                       <?php echo $person['status'] ? 'checked' : ''; ?>>
                                                <span class="checkmark"></span>
                                            </label>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($person['employee_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($person['department'] ?: 'N/A'); ?></td>
                                        <td>
                                            <select name="attendance[<?php echo $person['id']; ?>][status]" class="status-select">
                                                <option value="present" <?php echo $person['status'] === 'present' ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo $person['status'] === 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                <option value="late" <?php echo $person['status'] === 'late' ? 'selected' : ''; ?>>Late</option>
                                                <option value="half_day" <?php echo $person['status'] === 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[<?php echo $person['id']; ?>][check_in]" 
                                                   value="<?php echo $person['check_in_time']; ?>" class="time-input">
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[<?php echo $person['id']; ?>][check_out]" 
                                                   value="<?php echo $person['check_out_time']; ?>" class="time-input">
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[<?php echo $person['id']; ?>][notes]" 
                                                   value="<?php echo htmlspecialchars($person['notes'] ?? ''); ?>" 
                                                   placeholder="Optional notes..." class="notes-input">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 2rem; text-align: center;">
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4>No People Found</h4>
                            <p>No active people found for the selected criteria.</p>
                            <a href="../admin/people.php?action=add" class="btn btn-primary">Add People</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- Quick Attendance Marking -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Attendance - <?php echo date('F j, Y'); ?></h3>
                <p style="margin: 0.5rem 0 0 0; color: var(--text-light);">Click on a person to mark their attendance instantly.</p>
            </div>
            <div class="card-body">
                <?php if (!empty($people)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                        <?php foreach ($people as $person): ?>
                        <div class="person-card <?php echo $person['attendance_status'] ? 'marked' : ''; ?>" 
                             data-person-id="<?php echo $person['id']; ?>">
                            <div class="person-info">
                                <div class="person-avatar">
                                    <?php echo strtoupper(substr($person['first_name'], 0, 1) . substr($person['last_name'], 0, 1)); ?>
                                </div>
                                <div class="person-details">
                                    <h4><?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($person['employee_id']); ?></p>
                                    <p><?php echo htmlspecialchars($person['department'] ?: 'N/A'); ?></p>
                                </div>
                                <?php if ($person['attendance_status']): ?>
                                    <div class="attendance-status">
                                        <span class="badge badge-<?php 
                                            echo $person['attendance_status'] === 'present' ? 'success' : 
                                                ($person['attendance_status'] === 'late' ? 'warning' : 'error'); 
                                        ?>">
                                            <?php echo ucfirst($person['attendance_status']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="person-actions">
                                <button class="btn btn-small btn-success" onclick="markAttendance(<?php echo $person['id']; ?>, 'present')">
                                    <i class="fas fa-check"></i> Present
                                </button>
                                <button class="btn btn-small btn-warning" onclick="markAttendance(<?php echo $person['id']; ?>, 'late')">
                                    <i class="fas fa-clock"></i> Late
                                </button>
                                <button class="btn btn-small btn-error" onclick="markAttendance(<?php echo $person['id']; ?>, 'absent')">
                                    <i class="fas fa-times"></i> Absent
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No People Found</h4>
                        <p>No active people found in the system.</p>
                        <a href="../admin/people.php?action=add" class="btn btn-primary">Add People</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.status-select, .time-input, .notes-input {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.875rem;
    width: 100%;
}

.status-select:focus, .time-input:focus, .notes-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
}

.person-card {
    background: var(--bg-color);
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.person-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.person-card.marked {
    border-color: var(--success-color);
    background: rgba(16, 185, 129, 0.05);
}

.person-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.person-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
}

.person-details h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    color: var(--dark-color);
}

.person-details p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-light);
}

.attendance-status {
    margin-left: auto;
}

.person-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .person-card {
        padding: 1rem;
    }
    
    .person-actions {
        flex-direction: column;
    }
    
    .person-actions .btn {
        width: 100%;
    }
}
</style>

<script>
function markAllPresent() {
    const checkboxes = document.querySelectorAll('input[name*="[mark]"]');
    const statusSelects = document.querySelectorAll('select[name*="[status]"]');
    const timeInputs = document.querySelectorAll('input[name*="[check_in]"]');
    
    checkboxes.forEach(checkbox => checkbox.checked = true);
    statusSelects.forEach(select => select.value = 'present');
    timeInputs.forEach(input => {
        if (!input.value) {
            input.value = '<?php echo date("H:i"); ?>';
        }
    });
}

function clearAll() {
    const checkboxes = document.querySelectorAll('input[name*="[mark]"]');
    const statusSelects = document.querySelectorAll('select[name*="[status]"]');
    const timeInputs = document.querySelectorAll('input[type="time"]');
    const noteInputs = document.querySelectorAll('input[name*="[notes]"]');
    
    checkboxes.forEach(checkbox => checkbox.checked = false);
    statusSelects.forEach(select => select.value = 'present');
    timeInputs.forEach(input => input.value = '');
    noteInputs.forEach(input => input.value = '');
}

function markAttendance(personId, status) {
    const formData = new FormData();
    formData.append('person_id', personId);
    formData.append('status', status);
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<div class="spinner"></div>';
    button.disabled = true;
    
    fetch('?action=quick', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            AttendanceSystem.showMessage(data.message, 'success');
            const personCard = document.querySelector(`[data-person-id="${personId}"]`);
            personCard.classList.add('marked');
            
            // Update or add status badge
            let statusDiv = personCard.querySelector('.attendance-status');
            if (!statusDiv) {
                statusDiv = document.createElement('div');
                statusDiv.className = 'attendance-status';
                personCard.querySelector('.person-info').appendChild(statusDiv);
            }
            
            const badgeClass = status === 'present' ? 'success' : 
                              (status === 'late' ? 'warning' : 'error');
            statusDiv.innerHTML = `<span class="badge badge-${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
        } else {
            AttendanceSystem.showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        AttendanceSystem.showMessage('Network error occurred', 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>