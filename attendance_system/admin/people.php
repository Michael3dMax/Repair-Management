<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$pageTitle = 'People Management - Attendance Management System';

// Get database connection
$database = new Database();
$conn = $database->connect();

$action = $_GET['action'] ?? 'list';
$personId = $_GET['id'] ?? null;
$message = '';
$messageType = 'success';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employeeId = trim($_POST['employee_id']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $department = trim($_POST['department']);
        $position = trim($_POST['position']);
        $type = $_POST['type'];
        $status = $_POST['status'];
        
        // Validation
        if (empty($employeeId) || empty($firstName) || empty($lastName)) {
            $message = 'Employee ID, First Name, and Last Name are required.';
            $messageType = 'error';
        } else {
            try {
                if ($action === 'add') {
                    // Check if employee ID already exists
                    $stmt = $conn->prepare("SELECT id FROM people WHERE employee_id = ?");
                    $stmt->execute([$employeeId]);
                    if ($stmt->fetch()) {
                        $message = 'Employee ID already exists.';
                        $messageType = 'error';
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO people (employee_id, first_name, last_name, email, phone, department, position, type, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$employeeId, $firstName, $lastName, $email, $phone, $department, $position, $type, $status]);
                        $message = 'Person added successfully.';
                        $action = 'list';
                    }
                } else if ($action === 'edit' && $personId) {
                    // Check if employee ID exists for other records
                    $stmt = $conn->prepare("SELECT id FROM people WHERE employee_id = ? AND id != ?");
                    $stmt->execute([$employeeId, $personId]);
                    if ($stmt->fetch()) {
                        $message = 'Employee ID already exists.';
                        $messageType = 'error';
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE people 
                            SET employee_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, 
                                department = ?, position = ?, type = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$employeeId, $firstName, $lastName, $email, $phone, $department, $position, $type, $status, $personId]);
                        $message = 'Person updated successfully.';
                        $action = 'list';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $personId) {
    try {
        $stmt = $conn->prepare("UPDATE people SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$personId]);
        $message = 'Person deactivated successfully.';
        $action = 'list';
    } catch (PDOException $e) {
        $message = 'Error deactivating person: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get person data for edit
$person = null;
if ($action === 'edit' && $personId) {
    $stmt = $conn->prepare("SELECT * FROM people WHERE id = ?");
    $stmt->execute([$personId]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$person) {
        $message = 'Person not found.';
        $messageType = 'error';
        $action = 'list';
    }
}

// Get all people for list view
if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $department = $_GET['department'] ?? '';
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? 'active';
    
    $sql = "SELECT * FROM people WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR employee_id LIKE ? OR email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($department) {
        $sql .= " AND department = ?";
        $params[] = $department;
    }
    
    if ($type) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY first_name, last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique departments for filter
    $stmt = $conn->query("SELECT DISTINCT department FROM people WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Set session message
if ($message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $messageType;
}

require_once '../includes/header.php';
?>

<div class="container">
    <?php if ($action === 'list'): ?>
        <!-- List View -->
        <div class="page-header">
            <h1 class="page-title">People Management</h1>
            <p class="page-subtitle">Manage students and employees in the system.</p>
            <div class="page-actions">
                <a href="?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Person
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <input type="hidden" name="action" value="list">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" placeholder="Name, ID, or email..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="department">Department</label>
                            <select id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                            <?php echo ($_GET['department'] ?? '') === $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="type">Type</label>
                            <select id="type" name="type">
                                <option value="">All Types</option>
                                <option value="student" <?php echo ($_GET['type'] ?? '') === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="employee" <?php echo ($_GET['type'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo ($_GET['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="">All</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="?action=list" class="btn btn-outline">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- People Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">People List (<?php echo count($people); ?> found)</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($people)): ?>
                    <div class="table-container">
                        <table class="table data-table" id="peopleTable">
                            <thead>
                                <tr>
                                    <th data-sort>Employee ID</th>
                                    <th data-sort>Name</th>
                                    <th data-sort>Email</th>
                                    <th data-sort>Department</th>
                                    <th data-sort>Position</th>
                                    <th data-sort>Type</th>
                                    <th data-sort>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($people as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['employee_id']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
                                        <?php if ($p['phone']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($p['phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['email'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($p['department'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($p['position'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $p['type'] === 'student' ? 'info' : 'secondary'; ?>">
                                            <?php echo ucfirst($p['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $p['status'] === 'active' ? 'success' : 'error'; ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-small btn-outline">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($p['status'] === 'active'): ?>
                                                <a href="?action=delete&id=<?php echo $p['id']; ?>" 
                                                   class="btn btn-small btn-error"
                                                   onclick="return confirm('Are you sure you want to deactivate this person?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No People Found</h4>
                        <p>No people match your current filters.</p>
                        <a href="?action=add" class="btn btn-primary">Add First Person</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="page-header">
            <h1 class="page-title"><?php echo $action === 'add' ? 'Add New Person' : 'Edit Person'; ?></h1>
            <p class="page-subtitle"><?php echo $action === 'add' ? 'Add a new student or employee to the system.' : 'Update person information.'; ?></p>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" data-validate>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="form-group">
                            <label for="employee_id">Employee/Student ID *</label>
                            <input type="text" id="employee_id" name="employee_id" required
                                   value="<?php echo htmlspecialchars($person['employee_id'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="type">Type *</label>
                            <select id="type" name="type" required>
                                <option value="student" <?php echo ($person['type'] ?? 'student') === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="employee" <?php echo ($person['type'] ?? '') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?php echo htmlspecialchars($person['first_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?php echo htmlspecialchars($person['last_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($person['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?php echo htmlspecialchars($person['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="department">Department</label>
                            <input type="text" id="department" name="department"
                                   value="<?php echo htmlspecialchars($person['department'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="position">Position</label>
                            <input type="text" id="position" name="position"
                                   value="<?php echo htmlspecialchars($person['position'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo ($person['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($person['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? 'Add Person' : 'Update Person'; ?>
                        </button>
                        <a href="?action=list" class="btn btn-outline">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.filter-form input,
.filter-form select {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.875rem;
}

.filter-form input:focus,
.filter-form select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
}

@media (max-width: 768px) {
    .filter-form > div {
        grid-template-columns: 1fr !important;
    }
    
    .filter-form > div > div:last-child {
        justify-self: stretch;
    }
    
    .filter-form > div > div:last-child > div {
        width: 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>