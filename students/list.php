<?php
// students/list.php - Students listing page
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/crud.php';

require_permission('view_students');

$current_page = 'students';
$page_title = 'Students';

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : '';

// Build options for fetching students
$options = [
    'limit' => $records_per_page,
    'offset' => $offset,
    'search' => $search
];

// Add department filter
if ($department_filter) {
    $options['where']['s.department_id'] = $department_filter;
}

// Get students
$students = get_students($options);

// Get total count for pagination
$count_options = [];
if ($search) {
    $count_options['search'] = $search;
    $count_options['search_columns'] = ['u.first_name', 'u.last_name', 'u.email', 's.student_id'];
}
if ($department_filter) {
    $count_options['where']['s.department_id'] = $department_filter;
}

$total_students = count_records('students s JOIN users u ON s.user_id = u.id', $count_options);
$total_pages = ceil($total_students / $records_per_page);

// Get departments for filter
$departments = read_records('departments', ['order_by' => 'name ASC']);

// Success/Error messages
$success = isset($_GET['success']) ? sanitize_input($_GET['success']) : '';
$error = isset($_GET['error']) ? sanitize_input($_GET['error']) : '';

// Page header
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <h2 class="page-title">Students</h2>
        <div class="text-muted mt-1">Manage student records and information</div>
    </div>
    <div class="col-auto ms-auto">
        <div class="btn-list">
            <?php if (has_permission('manage_students')): ?>
            <a href="add.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Student
            </a>
            <?php endif; ?>
            <div class="btn-group">
                <button type="button" class="btn" data-bs-toggle="dropdown">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="1"></circle>
                        <circle cx="12" cy="5" r="1"></circle>
                        <circle cx="12" cy="19" r="1"></circle>
                    </svg>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="export.php?format=csv">Export as CSV</a>
                    <a class="dropdown-item" href="export.php?format=excel">Export as Excel</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$page_header = ob_get_clean();

// Page content
ob_start();
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Students List</h3>
        <div class="card-actions">
            <form method="GET" class="d-flex">
                <div class="input-group me-2">
                    <input type="text" name="search" class="form-control" placeholder="Search students..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
                <select name="department" class="form-select" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($search || $department_filter): ?>
                <a href="list.php" class="btn btn-outline-secondary ms-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <?php if (has_permission('manage_students')): ?>
                    <th class="w-1">Actions</th>
                    <?php endif; ?>
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
                            <?php if ($student['semester']): ?>
                                Semester <?php echo $student['semester']; ?>
                            <?php else: ?>
                                Not Set
                            <?php endif; ?>
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
                        <?php if (has_permission('manage_students')): ?>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="edit.php?id=<?php echo $student['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit
                                        </a>
                                        <a class="dropdown-item" href="grades.php?student_id=<?php echo $student['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                            Grades
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo has_permission('manage_students') ? '7' : '6'; ?>" class="text-center text-muted py-4">
                            <?php if ($search || $department_filter): ?>
                                No students found matching your criteria.
                            <?php else: ?>
                                No students found. <a href="add.php">Add the first student</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_students); ?> 
            of <?php echo $total_students; ?> entries
        </p>
        <ul class="pagination m-0 ms-auto">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    prev
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?>">
                    next
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete student <strong id="studentName"></strong>? This action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Yes, delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(studentId, studentName) {
    document.getElementById('studentName').textContent = studentName;
    document.getElementById('confirmDeleteBtn').href = 'delete.php?id=' + studentId + '&csrf_token=<?php echo generate_csrf_token(); ?>';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php
$content = ob_get_clean();

// Include the layout
include '../includes/layout.php';
?>