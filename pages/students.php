<?php
// pages/students.php - Complete Students Management System with Bulk Import

// Check if user has permission to view students
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'head_of_department', 'faculty'])) {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access this page.</p>
          </div>';
    return;
}

// Get action parameter
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get success/error messages from URL parameters
if (isset($_GET['success'])) {
    $message = sanitize_input($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = sanitize_input($_GET['error']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_student($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=students&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'bulk_import':
                $result = handle_bulk_import($_FILES, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=students&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_student($student_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=students&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_student($student_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=students&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=students&error=' . urlencode($result['message']));
                    exit;
                }
                break;
        }
    }
}

// Function to get all students with pagination and search
function get_all_students($options = []) {
    global $pdo;
    
    $defaults = [
        'search' => '',
        'department' => '',
        'semester' => '',
        'status' => '',
        'limit' => 20,
        'offset' => 0
    ];
    
    $options = array_merge($defaults, $options);
    
    $where_conditions = [];
    $params = [];
    
    // Search functionality
    if (!empty($options['search'])) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR s.student_id LIKE :search)";
        $params['search'] = '%' . $options['search'] . '%';
    }
    
    // Department filter
    if (!empty($options['department'])) {
        $where_conditions[] = "s.department_id = :department";
        $params['department'] = $options['department'];
    }
    
    // Semester filter
    if (!empty($options['semester'])) {
        $where_conditions[] = "s.semester = :semester";
        $params['semester'] = $options['semester'];
    }
    
    // Status filter
    if (!empty($options['status'])) {
        $where_conditions[] = "u.status = :status";
        $params['status'] = $options['status'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "
            SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.status, u.created_at,
                   d.name as department_name, d.code as department_code,
                   p.first_name as parent_first_name, p.last_name as parent_last_name, p.email as parent_email
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN departments d ON s.department_id = d.id
            LEFT JOIN users p ON s.parent_id = p.id
            {$where_clause}
            ORDER BY u.first_name, u.last_name
            LIMIT {$options['limit']} OFFSET {$options['offset']}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get students error: " . $e->getMessage());
        return [];
    }
}

// Function to count total students
function count_students($options = []) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($options['search'])) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR s.student_id LIKE :search)";
        $params['search'] = '%' . $options['search'] . '%';
    }
    
    if (!empty($options['department'])) {
        $where_conditions[] = "s.department_id = :department";
        $params['department'] = $options['department'];
    }
    
    if (!empty($options['semester'])) {
        $where_conditions[] = "s.semester = :semester";
        $params['semester'] = $options['semester'];
    }
    
    if (!empty($options['status'])) {
        $where_conditions[] = "u.status = :status";
        $params['status'] = $options['status'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "
            SELECT COUNT(*) as total
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN departments d ON s.department_id = d.id
            {$where_clause}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    } catch (Exception $e) {
        error_log("Count students error: " . $e->getMessage());
        return 0;
    }
}

// Function to get departments
function get_departments() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get departments error: " . $e->getMessage());
        return [];
    }
}

// Function to get student role ID
function get_student_role_id() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student'");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        error_log("Get student role error: " . $e->getMessage());
        return null;
    }
}

// Function to handle adding a single student
function handle_add_student($data) {
    global $pdo;
    
    // Validate input
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }
    
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($data['student_id'])) {
        $errors[] = "Student ID is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get student role ID
        $student_role_id = get_student_role_id();
        if (!$student_role_id) {
            throw new Exception("Student role not found.");
        }
        
        // Check for duplicate username, email, or student ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists.");
        }
        
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$data['student_id']]);
        if ($stmt->fetch()) {
            throw new Exception("Student ID already exists.");
        }
        
        // Create user account
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $student_role_id,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            'active'
        ]);
        
        if (!$result) {
            throw new Exception("Failed to create user account.");
        }
        
        $user_id = $pdo->lastInsertId();
        
        // Create student record
        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, student_id, department_id, semester, year_of_admission, parent_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_id,
            $data['student_id'],
            $data['department_id'] ?: null,
            $data['semester'] ?: null,
            $data['year_of_admission'] ?: date('Y'),
            $data['parent_id'] ?: null
        ]);
        
        if (!$result) {
            throw new Exception("Failed to create student record.");
        }
        
        $student_record_id = $pdo->lastInsertId();
        
        // Log the action
        log_audit($_SESSION['user_id'], 'create_student', 'students', $student_record_id, null, [
            'user_id' => $user_id,
            'student_id' => $data['student_id'],
            'name' => $data['first_name'] . ' ' . $data['last_name']
        ]);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Student added successfully.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Add student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to handle bulk import
function handle_bulk_import($files, $data) {
    global $pdo;
    
    if (!isset($files['bulk_file']) || $files['bulk_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Please select a valid CSV file.'];
    }
    
    $file = $files['bulk_file'];
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    
    if (!in_array($file['type'], $allowed_types) && !str_ends_with($file['name'], '.csv')) {
        return ['success' => false, 'message' => 'Only CSV files are allowed.'];
    }
    
    try {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception("Could not open CSV file.");
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception("CSV file is empty or invalid.");
        }
        
        // Expected columns
        $required_columns = ['first_name', 'last_name', 'email', 'student_id'];
        $optional_columns = ['username', 'phone', 'address', 'department_code', 'semester', 'year_of_admission'];
        
        // Map header to indices
        $column_map = [];
        foreach ($header as $index => $column) {
            $column = strtolower(trim($column));
            $column_map[$column] = $index;
        }
        
        // Check required columns
        foreach ($required_columns as $required) {
            if (!isset($column_map[$required])) {
                throw new Exception("Required column '{$required}' not found in CSV.");
            }
        }
        
        $pdo->beginTransaction();
        
        $student_role_id = get_student_role_id();
        if (!$student_role_id) {
            throw new Exception("Student role not found.");
        }
        
        // Get departments mapping
        $departments = [];
        $stmt = $pdo->query("SELECT id, code FROM departments");
        while ($dept = $stmt->fetch()) {
            $departments[strtoupper($dept['code'])] = $dept['id'];
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            try {
                // Extract data from row
                $student_data = [];
                foreach ($column_map as $column => $index) {
                    $student_data[$column] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                
                // Validate required fields
                foreach ($required_columns as $required) {
                    if (empty($student_data[$required])) {
                        throw new Exception("Row {$row_number}: {$required} is required.");
                    }
                }
                
                // Generate username if not provided
                if (empty($student_data['username'])) {
                    $student_data['username'] = strtolower($student_data['first_name'] . '.' . $student_data['last_name']);
                    // Remove spaces and special characters
                    $student_data['username'] = preg_replace('/[^a-z0-9._]/', '', $student_data['username']);
                }
                
                // Validate email
                if (!filter_var($student_data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Row {$row_number}: Invalid email format.");
                }
                
                // Check for duplicates
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$student_data['username'], $student_data['email']]);
                if ($stmt->fetch()) {
                    throw new Exception("Row {$row_number}: Username or email already exists.");
                }
                
                $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
                $stmt->execute([$student_data['student_id']]);
                if ($stmt->fetch()) {
                    throw new Exception("Row {$row_number}: Student ID already exists.");
                }
                
                // Get department ID
                $department_id = null;
                if (!empty($student_data['department_code'])) {
                    $dept_code = strtoupper($student_data['department_code']);
                    if (isset($departments[$dept_code])) {
                        $department_id = $departments[$dept_code];
                    }
                }
                
                // Generate default password
                $default_password = 'Student@' . date('Y');
                
                // Create user account
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                
                $result = $stmt->execute([
                    $student_data['username'],
                    $student_data['email'],
                    password_hash($default_password, PASSWORD_DEFAULT),
                    $student_role_id,
                    $student_data['first_name'],
                    $student_data['last_name'],
                    $student_data['phone'] ?? null,
                    $student_data['address'] ?? null
                ]);
                
                if (!$result) {
                    throw new Exception("Row {$row_number}: Failed to create user account.");
                }
                
                $user_id = $pdo->lastInsertId();
                
                // Create student record
                $stmt = $pdo->prepare("
                    INSERT INTO students (user_id, student_id, department_id, semester, year_of_admission) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $year_of_admission = !empty($student_data['year_of_admission']) ? $student_data['year_of_admission'] : date('Y');
                $semester = !empty($student_data['semester']) ? $student_data['semester'] : null;
                
                $result = $stmt->execute([
                    $user_id,
                    $student_data['student_id'],
                    $department_id,
                    $semester,
                    $year_of_admission
                ]);
                
                if (!$result) {
                    throw new Exception("Row {$row_number}: Failed to create student record.");
                }
                
                $success_count++;
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $error_count++;
                
                // Skip this row and continue with next
                continue;
            }
        }
        
        fclose($handle);
        
        if ($success_count > 0) {
            // Log bulk import action
            log_audit($_SESSION['user_id'], 'bulk_import_students', 'students', null, null, [
                'success_count' => $success_count,
                'error_count' => $error_count,
                'file_name' => $file['name']
            ]);
            
            $pdo->commit();
            
            $message = "Bulk import completed: {$success_count} students added successfully.";
            if ($error_count > 0) {
                $message .= " {$error_count} rows had errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more errors.";
                }
            }
            
            return ['success' => true, 'message' => $message];
        } else {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'No students were imported. Errors: ' . implode('; ', $errors)];
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk import error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to handle editing a student
function handle_edit_student($id, $data) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid student ID.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get current student data
        $stmt = $pdo->prepare("
            SELECT s.*, u.* FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $current_student = $stmt->fetch();
        
        if (!$current_student) {
            throw new Exception("Student not found.");
        }
        
        // Check for duplicate email (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $current_student['user_id']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists.");
        }
        
        // Update user record
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $current_student['user_id']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to update user information.");
        }
        
        // Update student record
        $stmt = $pdo->prepare("
            UPDATE students 
            SET department_id = ?, semester = ?, year_of_admission = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['department_id'] ?: null,
            $data['semester'] ?: null,
            $data['year_of_admission'] ?: $current_student['year_of_admission'],
            $id
        ]);
        
        if (!$result) {
            throw new Exception("Failed to update student information.");
        }
        
        // Log the action
        log_audit($_SESSION['user_id'], 'update_student', 'students', $id, $current_student, $data);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Student updated successfully.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Edit student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to handle deleting a student
function handle_delete_student($id) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid student ID.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get student data for audit
        $stmt = $pdo->prepare("
            SELECT s.*, u.first_name, u.last_name FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            throw new Exception("Student not found.");
        }
        
        // Delete student record (this will cascade to user due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if (!$result || $stmt->rowCount() === 0) {
            throw new Exception("Failed to delete student.");
        }
        
        // Delete associated user account
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$student['user_id']]);
        
        // Log the action
        log_audit($_SESSION['user_id'], 'delete_student', 'students', $id, $student, null);
        
        $pdo->commit();
        return ['success' => true, 'message' => 'Student deleted successfully.'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete student error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get student by ID for editing
function get_student_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.status,
                   d.name as department_name
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            LEFT JOIN departments d ON s.department_id = d.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get student error: " . $e->getMessage());
        return false;
    }
}

// Pagination and filtering setup
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$department_filter = isset($_GET['department']) ? (int)$_GET['department'] : '';
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$filter_options = [
    'search' => $search,
    'department' => $department_filter,
    'semester' => $semester_filter,
    'status' => $status_filter,
    'limit' => $records_per_page,
    'offset' => $offset
];

// Get data based on current action
$students = [];
$departments = get_departments();
$edit_student = null;

if ($action === 'list' || $action === 'delete') {
    $students = get_all_students($filter_options);
    $total_students = count_students($filter_options);
    $total_pages = ceil($total_students / $records_per_page);
}

if ($action === 'edit' && $student_id) {
    $edit_student = get_student_by_id($student_id);
    if (!$edit_student) {
        $error = 'Student not found.';
        $action = 'list';
        $students = get_all_students($filter_options);
        $total_students = count_students($filter_options);
        $total_pages = ceil($total_students / $records_per_page);
    }
}
?>

<!-- Messages -->
<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $message; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $error; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
<!-- Add Student Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Student</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=students" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Students
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=students&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <div class="form-hint">Username must be unique and at least 3 characters.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Student ID</label>
                        <input type="text" name="student_id" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                        <div class="form-hint">Unique student identifier.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-hint">Password must be at least 8 characters.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo (($_POST['department_id'] ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo (($_POST['semester'] ?? '') == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Year of Admission</label>
                        <input type="number" name="year_of_admission" class="form-control" 
                               min="2000" max="<?php echo date('Y') + 1; ?>"
                               value="<?php echo $_POST['year_of_admission'] ?? date('Y'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Add Student
                </button>
                <a href="dashboard.php?page=students" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'bulk_import'): ?>
<!-- Bulk Import Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bulk Import Students</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=students" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Students
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4>CSV Format Requirements</h4>
            <p>Your CSV file must include the following columns (case-sensitive):</p>
            <ul>
                <li><strong>Required:</strong> first_name, last_name, email, student_id</li>
                <li><strong>Optional:</strong> username, phone, address, department_code, semester, year_of_admission</li>
            </ul>
            <p class="mb-0"><strong>Notes:</strong></p>
            <ul class="mb-0">
                <li>If username is not provided, it will be generated automatically</li>
                <li>Default password will be "Student@<?php echo date('Y'); ?>" for all imported students</li>
                <li>Department code should match existing department codes in the system</li>
                <li>All students will be assigned the "student" role automatically</li>
            </ul>
        </div>
        
        <div class="mb-3">
            <a href="download_sample.php" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Download Sample CSV Template
            </a>
        </div>
        
        <form method="POST" action="dashboard.php?page=students&action=bulk_import" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">CSV File</label>
                <input type="file" name="bulk_file" class="form-control" accept=".csv" required>
                <div class="form-hint">Select a CSV file containing student data. Maximum file size: 10MB.</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Import Students
                </button>
                <a href="dashboard.php?page=students" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_student): ?>
<!-- Edit Student Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Student: <?php echo htmlspecialchars($edit_student['first_name'] . ' ' . $edit_student['last_name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=students" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Students
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=students&action=edit&id=<?php echo $edit_student['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? $edit_student['first_name']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? $edit_student['last_name']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_student['student_id']); ?>" readonly>
                        <div class="form-hint">Student ID cannot be changed.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? $edit_student['email']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo (($_POST['department_id'] ?? $edit_student['department_id']) == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Select Semester</option>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?php echo $i; ?>" 
                                    <?php echo (($_POST['semester'] ?? $edit_student['semester']) == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Year of Admission</label>
                        <input type="number" name="year_of_admission" class="form-control" 
                               min="2000" max="<?php echo date('Y') + 1; ?>"
                               value="<?php echo $_POST['year_of_admission'] ?? $edit_student['year_of_admission']; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $edit_student['phone']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $edit_student['address']); ?></textarea>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Student
                </button>
                <a href="dashboard.php?page=students" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Students List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Students Management</h3>
        <div class="card-actions">
            <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department'): ?>
            <div class="btn-group">
                <a href="dashboard.php?page=students&action=add" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Add Student
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="dashboard.php?page=students&action=bulk_import">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Bulk Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="export_students.php?format=csv">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7,10 12,15 17,10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export CSV
                    </a>
                    <a class="dropdown-item" href="export_students.php?format=excel">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                        </svg>
                        Export Excel
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card-body border-bottom">
        <form method="GET" action="dashboard.php" class="row g-2">
            <input type="hidden" name="page" value="students">
            
            <div class="col-md-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search students..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="department" class="form-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="semester" class="form-select" onchange="this.form.submit()">
                    <option value="">All Semesters</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $semester_filter == $i ? 'selected' : ''; ?>>
                        Semester <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <?php if ($search || $department_filter || $semester_filter || $status_filter): ?>
                <a href="dashboard.php?page=students" class="btn btn-outline-secondary">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
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
                    <th>Year</th>
                    <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department'): ?>
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
                            <?php if ($student['department_name']): ?>
                                <?php echo htmlspecialchars($student['department_name']); ?>
                                <small class="d-block text-muted"><?php echo htmlspecialchars($student['department_code']); ?></small>
                            <?php else: ?>
                                <span class="text-muted">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($student['semester']): ?>
                                <span class="badge bg-gray-lt">Semester <?php echo $student['semester']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($student['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" class="text-reset">
                                    <?php echo htmlspecialchars($student['phone']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No phone</span>
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
                        <td class="text-muted">
                            <?php echo $student['year_of_admission']; ?>
                        </td>
                        <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department'): ?>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=students&action=view&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=students&action=edit&id=<?php echo $student['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit Student
                                        </a>
                                        <a class="dropdown-item" href="dashboard.php?page=students&action=grades&id=<?php echo $student['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                            View Grades
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteStudent(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department') ? '8' : '7'; ?>" class="text-center text-muted py-4">
                            <?php if ($search || $department_filter || $semester_filter || $status_filter): ?>
                                No students found matching your criteria. <a href="dashboard.php?page=students">Clear filters</a> to see all students.
                            <?php else: ?>
                                No students found. <a href="dashboard.php?page=students&action=add">Add the first student</a> or <a href="dashboard.php?page=students&action=bulk_import">import students</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (isset($total_pages) && $total_pages > 1): ?>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_students); ?> 
            of <?php echo $total_students; ?> entries
        </p>
        <ul class="pagination m-0 ms-auto">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=students&page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?><?php echo $semester_filter ? '&semester=' . $semester_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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
                <a class="page-link" href="?page=students&page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?><?php echo $semester_filter ? '&semester=' . $semester_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=students&page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $department_filter ? '&department=' . $department_filter : ''; ?><?php echo $semester_filter ? '&semester=' . $semester_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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

<!-- Student Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Students</div>
                </div>
                <div class="h1 mb-3"><?php echo isset($total_students) ? number_format($total_students) : count($students); ?></div>
                <div class="d-flex mb-2">
                    <div>All registered students</div>
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
                    <div>Currently active</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">New This Year</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($students, fn($s) => $s['year_of_admission'] == date('Y'))); ?></div>
                <div class="d-flex mb-2">
                    <div>Admitted in <?php echo date('Y'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Departments</div>
                </div>
                <div class="h1 mb-3"><?php echo count($departments); ?></div>
                <div class="d-flex mb-2">
                    <div>Available departments</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteStudentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete student <strong id="deleteStudentName"></strong>? This action cannot be undone and will remove all associated data including grades and attendance records.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteStudentForm" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="btn btn-danger">Yes, delete student</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, 'dashboard.php?page=students');
}

// Confirm delete student
function confirmDeleteStudent(studentId, studentName) {
    document.getElementById('deleteStudentName').textContent = studentName;
    document.getElementById('deleteStudentForm').action = `dashboard.php?page=students&action=delete&id=${studentId}`;
    const modal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
    modal.show();
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showMessage('danger', 'Please fill in all required fields.');
            }
            
            // Additional validation for bulk import
            if (form.querySelector('input[name="bulk_file"]')) {
                const fileInput = form.querySelector('input[name="bulk_file"]');
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    if (file.size > 10 * 1024 * 1024) { // 10MB
                        e.preventDefault();
                        showMessage('danger', 'File size must be less than 10MB.');
                        return;
                    }
                    if (!file.name.toLowerCase().endsWith('.csv')) {
                        e.preventDefault();
                        showMessage('danger', 'Please select a CSV file.');
                        return;
                    }
                }
            }
        });
    });
    
    // Add input event listeners to remove invalid class when user starts typing
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    // Auto-generate username from name fields
    const firstNameInput = document.querySelector('input[name="first_name"]');
    const lastNameInput = document.querySelector('input[name="last_name"]');
    const usernameInput = document.querySelector('input[name="username"]');
    
    if (firstNameInput && lastNameInput && usernameInput) {
        function generateUsername() {
            if (firstNameInput.value && lastNameInput.value && !usernameInput.value) {
                const username = (firstNameInput.value + '.' + lastNameInput.value)
                    .toLowerCase()
                    .replace(/[^a-z0-9.]/g, '');
                usernameInput.value = username;
            }
        }
        
        firstNameInput.addEventListener('blur', generateUsername);
        lastNameInput.addEventListener('blur', generateUsername);
    }
    
    // File input validation
    const fileInput = document.querySelector('input[name="bulk_file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Check file size
                if (file.size > 10 * 1024 * 1024) {
                    showMessage('danger', 'File size must be less than 10MB.');
                    this.value = '';
                    return;
                }
                
                // Check file type
                if (!file.name.toLowerCase().endsWith('.csv')) {
                    showMessage('danger', 'Please select a CSV file.');
                    this.value = '';
                    return;
                }
                
                showMessage('info', `Selected file: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`);
            }
        });
    }
});

// Enhanced message display function
function showMessage(type, message) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.alert.auto-message');
    existingMessages.forEach(msg => msg.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible auto-message fade show`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
    `;
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : type === 'info' ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.auto-message)');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-dismissible')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }
        });
    }, 5000);
});

// Enhanced search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('form[action="dashboard.php"]');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');
        if (searchInput) {
            // Add search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchForm.submit();
                }
            });
        }
    }
});
</script>

<?php
// Create download_sample.php for CSV template
if (!file_exists('download_sample.php')) {
    $sample_content = '<?php
// download_sample.php - Download CSV template for bulk import
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=student_import_template.csv");

$sample_data = [
    ["first_name", "last_name", "email", "student_id", "username", "phone", "address", "department_code", "semester", "year_of_admission"],
    ["John", "Doe", "john.doe@example.com", "STU001", "john.doe", "+1234567890", "123 Main St", "CS", "1", "2024"],
    ["Jane", "Smith", "jane.smith@example.com", "STU002", "jane.smith", "+1234567891", "456 Oak Ave", "EE", "3", "2023"],
    ["Bob", "Johnson", "bob.johnson@example.com", "STU003", "", "+1234567892", "", "ME", "5", "2022"]
];

$output = fopen("php://output", "w");
foreach ($sample_data as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>';
    file_put_contents('download_sample.php', $sample_content);
}
?>