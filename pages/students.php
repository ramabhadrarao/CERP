<?php
// pages/students.php - Students management page content

// Check if user has permission to view students
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'head_of_department', 'faculty'])) {
    echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
    return;
}

// Get action parameter
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';

// Get students from database
try {
    $stmt = $pdo->query("
        SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.status, 
               d.name as department_name
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY u.first_name, u.last_name
        LIMIT 50
    ");
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Students page error: " . $e->getMessage());
    $students = [];
}
?>

<?php if ($action === 'add'): ?>
<!-- Add Student Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Student</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4>Add Student Feature</h4>
            <p>This is a placeholder for the add student functionality. In a complete implementation, this would include:</p>
            <ul>
                <li>Student registration form</li>
                <li>Form validation</li>
                <li>Database insertion</li>
                <li>Email notifications</li>
            </ul>
        </div>
        <a href="dashboard.php?page=students" class="btn btn-secondary">Back to Students List</a>
    </div>
</div>

<?php elseif ($action === 'grades'): ?>
<!-- Grades Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Student Grades</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4>Grades Management</h4>
            <p>This section would include:</p>
            <ul>
                <li>Grade entry forms</li>
                <li>Grade reports</li>
                <li>GPA calculations</li>
                <li>Transcript generation</li>
            </ul>
        </div>
        <a href="dashboard.php?page=students" class="btn btn-secondary">Back to Students List</a>
    </div>
</div>

<?php else: ?>
<!-- Students List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Students List</h3>
        <div class="card-actions">
            <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department'): ?>
            <a href="dashboard.php?page=students&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Student
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Department</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td>
                            <div class="d-flex py-1 align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill">
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </div>
                                    <div class="text-muted"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-blue-lt"><?php echo htmlspecialchars($student['student_id']); ?></span>
                        </td>
                        <td class="text-muted">
                            <?php echo htmlspecialchars($student['department_name'] ?: 'Not Assigned'); ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($student['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" class="text-reset">
                                    <?php echo htmlspecialchars($student['phone']); ?>
                                </a>
                            <?php else: ?>
                                No phone
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_class = match($student['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                'suspended' => 'bg-red',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=students&action=view&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=students&action=edit&id=<?php echo $student['id']; ?>">
                                            Edit
                                        </a>
                                        <a class="dropdown-item" href="dashboard.php?page=students&action=grades&id=<?php echo $student['id']; ?>">
                                            Grades
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No students found. <a href="dashboard.php?page=students&action=add">Add the first student</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Stats -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Students</div>
                </div>
                <div class="h1 mb-3"><?php echo count($students); ?></div>
                <div class="d-flex mb-2">
                    <div>Currently enrolled</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Students</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($students, fn($s) => $s['status'] === 'active')); ?></div>
                <div class="d-flex mb-2">
                    <div>Status: Active</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>