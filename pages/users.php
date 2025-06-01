<?php
// pages/users.php - Complete Enhanced User Management System

// Check if user has admin permission
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access user management. Only super administrators can manage users.</p>
          </div>';
    return;
}

// Get action and parameters
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
                $result = handle_add_user($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=users&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_user($user_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=users&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_user($user_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=users&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=users&error=' . urlencode($result['message']));
                    exit;
                }
                break;
                
            case 'bulk_import':
                $result = handle_bulk_import_users($_FILES, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=users&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Handle AJAX requests for quick actions
if (isset($_GET['ajax']) && isset($_GET['id'])) {
    $ajax_user_id = (int)$_GET['id'];
    
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    
    switch ($_GET['ajax']) {
        case 'toggle_status':
            $result = toggle_user_status($ajax_user_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'reset_password':
            $result = reset_user_password($ajax_user_id);
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
    }
}

// Enhanced function to get all users with pagination and search
function get_all_users($options = []) {
    global $pdo;
    
    $defaults = [
        'search' => '',
        'role_filter' => '',
        'status_filter' => '',
        'limit' => 25,
        'offset' => 0
    ];
    
    $options = array_merge($defaults, $options);
    
    $where_conditions = [];
    $params = [];
    
    // Search functionality
    if (!empty($options['search'])) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
        $params['search'] = '%' . $options['search'] . '%';
    }
    
    // Role filter
    if (!empty($options['role_filter'])) {
        $where_conditions[] = "u.role_id = :role_filter";
        $params['role_filter'] = $options['role_filter'];
    }
    
    // Status filter
    if (!empty($options['status_filter'])) {
        $where_conditions[] = "u.status = :status_filter";
        $params['status_filter'] = $options['status_filter'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "
            SELECT u.*, r.name as role_name, r.description as role_description,
                   COUNT(CASE WHEN us.expires_at > NOW() THEN 1 END) as active_sessions,
                   u.created_at, u.last_login
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN user_sessions us ON u.id = us.user_id
            {$where_clause}
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT {$options['limit']} OFFSET {$options['offset']}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return [];
    }
}

// Count total users
function count_users($options = []) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($options['search'])) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
        $params['search'] = '%' . $options['search'] . '%';
    }
    
    if (!empty($options['role_filter'])) {
        $where_conditions[] = "u.role_id = :role_filter";
        $params['role_filter'] = $options['role_filter'];
    }
    
    if (!empty($options['status_filter'])) {
        $where_conditions[] = "u.status = :status_filter";
        $params['status_filter'] = $options['status_filter'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "SELECT COUNT(*) as total FROM users u LEFT JOIN roles r ON u.role_id = r.id {$where_clause}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    } catch (Exception $e) {
        error_log("Count users error: " . $e->getMessage());
        return 0;
    }
}

// Get all roles for dropdowns
function get_all_roles() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM roles WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get roles error: " . $e->getMessage());
        return [];
    }
}

// Get single user for editing
function get_user_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

// Enhanced handle add user
function handle_add_user($data) {
    global $pdo;
    
    // Validate input
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['username'])) {
        $errors[] = "Username can only contain letters, numbers, dots, underscores, and hyphens.";
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
    
    if (empty($data['role_id']) || !is_numeric($data['role_id'])) {
        $errors[] = "Valid role selection is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists.'];
        }
        
        // Generate UUID for user
        $user_uuid = generate_uuid();
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (user_uuid, username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_uuid,
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role_id'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            $new_user_id = $pdo->lastInsertId();
            
            // Log the action
            log_audit($_SESSION['user_id'], 'create_user', 'users', $new_user_id, null, [
                'username' => $data['username'],
                'email' => $data['email'],
                'role_id' => $data['role_id']
            ]);
            
            return ['success' => true, 'message' => 'User created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
        
    } catch (Exception $e) {
        error_log("Add user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle edit user
function handle_edit_user($id, $data) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['username'])) {
        $errors[] = "Username can only contain letters, numbers, dots, underscores, and hyphens.";
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($data['role_id']) || !is_numeric($data['role_id'])) {
        $errors[] = "Valid role selection is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Get old values for audit
        $old_user = get_user_by_id($id);
        if (!$old_user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Check for duplicate username/email (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$data['username'], $data['email'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists.'];
        }
        
        // Update user
        $sql = "UPDATE users SET username = ?, email = ?, role_id = ?, first_name = ?, last_name = ?, phone = ?, address = ?, status = ?, updated_at = NOW()";
        $params = [
            $data['username'],
            $data['email'],
            $data['role_id'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active'
        ];
        
        // Update password if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
            }
            $sql .= ", password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'update_user', 'users', $id, $old_user, $data);
            
            return ['success' => true, 'message' => 'User updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user.'];
        }
        
    } catch (Exception $e) {
        error_log("Edit user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle delete user
function handle_delete_user($id) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'You cannot delete your own account.'];
    }
    
    try {
        // Get user info for audit
        $user = get_user_by_id($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Check if user has dependent records
        $dependent_tables = [
            'students' => 'user_id',
            'faculty' => 'user_id'
        ];
        
        foreach ($dependent_tables as $table => $column) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => "Cannot delete user. They have associated {$table} records."];
            }
        }
        
        // Delete user (sessions will be cascaded)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            log_audit($_SESSION['user_id'], 'delete_user', 'users', $id, $user, null);
            
            return ['success' => true, 'message' => 'User deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Cannot delete user. They may have associated records.'];
    }
}

// Toggle user status
function toggle_user_status($id) {
    global $pdo;
    
    if (!$id || $id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot modify this user.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $id]);
        
        if ($result) {
            log_audit($_SESSION['user_id'], 'toggle_status', 'users', $id, ['status' => $user['status']], ['status' => $new_status]);
            return ['success' => true, 'message' => 'User status updated.', 'new_status' => $new_status];
        }
        
        return ['success' => false, 'message' => 'Failed to update status.'];
        
    } catch (Exception $e) {
        error_log("Toggle status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Reset user password
function reset_user_password($id) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    try {
        $new_password = generate_secure_password(12);
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$password_hash, $id]);
        
        if ($result) {
            log_audit($_SESSION['user_id'], 'reset_password', 'users', $id, null, null);
            return ['success' => true, 'message' => 'Password reset successfully.', 'new_password' => $new_password];
        }
        
        return ['success' => false, 'message' => 'Failed to reset password.'];
        
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Bulk import users
function handle_bulk_import_users($files, $data) {
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
        $required_columns = ['first_name', 'last_name', 'email', 'username', 'role'];
        $optional_columns = ['phone', 'address', 'status'];
        
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
        
        // Get roles mapping
        $roles = [];
        $stmt = $pdo->query("SELECT id, name FROM roles WHERE status = 'active'");
        while ($role = $stmt->fetch()) {
            $roles[strtolower($role['name'])] = $role['id'];
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            try {
                // Extract data from row
                $user_data = [];
                foreach ($column_map as $column => $index) {
                    $user_data[$column] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                
                // Validate required fields
                foreach ($required_columns as $required) {
                    if (empty($user_data[$required])) {
                        throw new Exception("Row {$row_number}: {$required} is required.");
                    }
                }
                
                // Validate email
                if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Row {$row_number}: Invalid email format.");
                }
                
                // Get role ID
                $role_name = strtolower($user_data['role']);
                if (!isset($roles[$role_name])) {
                    throw new Exception("Row {$row_number}: Invalid role '{$user_data['role']}'.");
                }
                $role_id = $roles[$role_name];
                
                // Check for duplicates
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$user_data['username'], $user_data['email']]);
                if ($stmt->fetch()) {
                    throw new Exception("Row {$row_number}: Username or email already exists.");
                }
                
                // Generate default password
                $default_password = 'Welcome@' . date('Y');
                $user_uuid = generate_uuid();
                
                // Create user account
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_uuid, username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $user_uuid,
                    $user_data['username'],
                    $user_data['email'],
                    password_hash($default_password, PASSWORD_DEFAULT),
                    $role_id,
                    $user_data['first_name'],
                    $user_data['last_name'],
                    $user_data['phone'] ?? null,
                    $user_data['address'] ?? null,
                    $user_data['status'] ?? 'active'
                ]);
                
                if (!$result) {
                    throw new Exception("Row {$row_number}: Failed to create user account.");
                }
                
                $success_count++;
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $error_count++;
                continue;
            }
        }
        
        fclose($handle);
        
        if ($success_count > 0) {
            // Log bulk import action
            log_audit($_SESSION['user_id'], 'bulk_import_users', 'users', null, null, [
                'success_count' => $success_count,
                'error_count' => $error_count,
                'file_name' => $file['name']
            ]);
            
            $pdo->commit();
            
            $message = "Bulk import completed: {$success_count} users added successfully.";
            if ($error_count > 0) {
                $message .= " {$error_count} rows had errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more errors.";
                }
            }
            
            return ['success' => true, 'message' => $message];
        } else {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'No users were imported. Errors: ' . implode('; ', $errors)];
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk import error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Pagination and filtering setup
$records_per_page = 25;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? (int)$_GET['role'] : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$filter_options = [
    'search' => $search,
    'role_filter' => $role_filter,
    'status_filter' => $status_filter,
    'limit' => $records_per_page,
    'offset' => $offset
];

// Get data based on current action
$users = [];
$roles = [];
$edit_user = null;

if ($action === 'list' || $action === 'delete') {
    $users = get_all_users($filter_options);
    $total_users = count_users($filter_options);
    $total_pages = ceil($total_users / $records_per_page);
}

if ($action === 'add' || $action === 'edit' || $action === 'bulk_import') {
    $roles = get_all_roles();
    if ($action === 'edit' && $user_id) {
        $edit_user = get_user_by_id($user_id);
        if (!$edit_user) {
            $error = 'User not found.';
            $action = 'list';
            $users = get_all_users($filter_options);
            $total_users = count_users($filter_options);
            $total_pages = ceil($total_users / $records_per_page);
        }
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
<!-- Add User Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New User</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=users" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Users
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=users&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <div class="form-hint">Username must be unique and at least 3 characters long.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="form-hint">Email must be unique and valid.</div>
                    </div>
                </div>
            </div>
            
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
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <div class="form-hint">Password must be at least 8 characters long.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Role</label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" 
                                    <?php echo (($_POST['role_id'] ?? '') == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                                <?php if ($role['description']): ?>
                                    - <?php echo htmlspecialchars($role['description']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
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
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo (($_POST['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
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
                    Create User
                </button>
                <a href="dashboard.php?page=users" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'bulk_import'): ?>
<!-- Bulk Import Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bulk Import Users</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=users" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Users
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h4>CSV Format Requirements</h4>
            <p>Your CSV file must include the following columns (case-sensitive):</p>
            <ul>
                <li><strong>Required:</strong> first_name, last_name, email, username, role</li>
                <li><strong>Optional:</strong> phone, address, status</li>
            </ul>
            <p class="mb-0"><strong>Notes:</strong></p>
            <ul class="mb-0">
                <li>Default password will be "Welcome@<?php echo date('Y'); ?>" for all imported users</li>
                <li>Role should match existing role names (e.g., faculty, student, admin)</li>
                <li>Status defaults to "active" if not specified</li>
            </ul>
        </div>
        
        <form method="POST" action="dashboard.php?page=users&action=bulk_import" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">CSV File</label>
                <input type="file" name="bulk_file" class="form-control" accept=".csv" required>
                <div class="form-hint">Select a CSV file containing user data. Maximum file size: 10MB.</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Import Users
                </button>
                <a href="dashboard.php?page=users" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_user): ?>
<!-- Edit User Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit User: <?php echo htmlspecialchars($edit_user['first_name'] . ' ' . $edit_user['last_name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=users" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Users
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=users&action=edit&id=<?php echo $edit_user['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? $edit_user['username']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? $edit_user['email']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? $edit_user['first_name']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? $edit_user['last_name']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control">
                        <div class="form-hint">Leave empty to keep current password. Must be at least 8 characters if changed.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Role</label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" 
                                    <?php echo (($_POST['role_id'] ?? $edit_user['role_id']) == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                                <?php if ($role['description']): ?>
                                    - <?php echo htmlspecialchars($role['description']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $edit_user['phone']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (($_POST['status'] ?? $edit_user['status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? $edit_user['status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo (($_POST['status'] ?? $edit_user['status']) === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            <option value="pending" <?php echo (($_POST['status'] ?? $edit_user['status']) === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? $edit_user['address']); ?></textarea>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update User
                </button>
                <a href="dashboard.php?page=users" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">User Management</h3>
        <div class="card-actions">
            <div class="btn-group">
                <a href="dashboard.php?page=users&action=add" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Add User
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="dashboard.php?page=users&action=bulk_import">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Bulk Import
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card-body border-bottom">
        <form method="GET" action="dashboard.php" class="row g-2">
            <input type="hidden" name="page" value="users">
            
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo $role_filter == $role['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <?php if ($search || $role_filter || $status_filter): ?>
                <a href="dashboard.php?page=users" class="btn btn-outline-secondary w-100">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Sessions</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $list_user): ?>
                    <tr>
                        <td>
                            <div class="d-flex py-1 align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($list_user['first_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill">
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars($list_user['first_name'] . ' ' . $list_user['last_name']); ?>
                                        <?php if ($list_user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-blue-lt ms-1">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted">
                                        <?php echo htmlspecialchars($list_user['username']); ?> • 
                                        <?php echo htmlspecialchars($list_user['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-purple-lt">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $list_user['role_name'] ?? 'Unknown'))); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_class = match($list_user['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                'suspended' => 'bg-red',
                                'pending' => 'bg-yellow',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>" id="status-<?php echo $list_user['id']; ?>">
                                <?php echo ucfirst($list_user['status']); ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($list_user['created_at'])); ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($list_user['last_login']): ?>
                                <?php echo date('M j, Y g:i A', strtotime($list_user['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($list_user['active_sessions'] > 0): ?>
                                <span class="badge bg-green"><?php echo $list_user['active_sessions']; ?> active</span>
                            <?php else: ?>
                                <span class="text-muted">No sessions</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <?php if ($list_user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="toggleUserStatus(<?php echo $list_user['id']; ?>)" id="toggle-btn-<?php echo $list_user['id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=users&action=edit&id=<?php echo $list_user['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit User
                                        </a>
                                        
                                        <?php if ($list_user['id'] != $_SESSION['user_id']): ?>
                                        <button class="dropdown-item" onclick="resetUserPassword(<?php echo $list_user['id']; ?>, '<?php echo htmlspecialchars($list_user['first_name'] . ' ' . $list_user['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <circle cx="12" cy="16" r="1"></circle>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                            Reset Password
                                        </button>
                                        
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteUser(<?php echo $list_user['id']; ?>, '<?php echo htmlspecialchars($list_user['first_name'] . ' ' . $list_user['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete User
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <?php if ($search || $role_filter || $status_filter): ?>
                                No users found matching your criteria. <a href="dashboard.php?page=users">Clear filters</a> to see all users.
                            <?php else: ?>
                                No users found. <a href="dashboard.php?page=users&action=add">Add the first user</a>.
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
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_users); ?> 
            of <?php echo $total_users; ?> entries
        </p>
        <ul class="pagination m-0 ms-auto">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&page_num=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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
                <a class="page-link" href="?page=users&page_num=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&page_num=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
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

<!-- User Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Users</div>
                </div>
                <div class="h1 mb-3"><?php echo isset($total_users) ? number_format($total_users) : count($users); ?></div>
                <div class="d-flex mb-2">
                    <div>All registered users</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Users</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => $u['status'] === 'active')); ?></div>
                <div class="d-flex mb-2">
                    <div>Status: Active</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Online Users</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => $u['active_sessions'] > 0)); ?></div>
                <div class="d-flex mb-2">
                    <div>Currently logged in</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Administrators</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => in_array($u['role_name'], ['super_admin', 'admin']))); ?></div>
                <div class="d-flex mb-2">
                    <div>Admin users</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete user <strong id="deleteUserName"></strong>? This action cannot be undone and will remove all associated data.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteUserForm" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="btn btn-danger">Yes, delete user</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Result Modal -->
<div class="modal fade" id="passwordResetModal" tabindex="-1" aria-labelledby="passwordResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordResetModalLabel">Password Reset Successful</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success mb-3">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">New Password Generated</h4>
                            <p class="mb-0">The password has been reset successfully. Please share this new password securely with the user.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password:</label>
                    <div class="input-group">
                        <input type="text" id="newPassword" class="form-control" readonly value="Loading...">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()" title="Copy to clipboard">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h5>Important Security Notice:</h5>
                    <ul class="mb-0">
                        <li>Share this password through a secure channel</li>
                        <li>The user should change this password after their first login</li>
                        <li>This password will only be shown once</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyPassword()">Copy Password</button>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, 'dashboard.php?page=users');
}

// CSRF token for AJAX requests
const csrfToken = '<?php echo generate_csrf_token(); ?>';

// Toggle user status via AJAX
function toggleUserStatus(userId) {
    if (confirm('Are you sure you want to change this user\'s status?')) {
        const button = document.getElementById(`toggle-btn-${userId}`);
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        button.disabled = true;
        
        // Create a simple AJAX call
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `dashboard.php?page=users&ajax=toggle_status&id=${userId}&token=${encodeURIComponent(csrfToken)}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            const statusBadge = document.getElementById(`status-${userId}`);
                            statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                            statusBadge.className = `badge ${data.new_status === 'active' ? 'bg-green' : 'bg-gray'}`;
                            showMessage('success', data.message);
                        } else {
                            showMessage('danger', data.message || 'Unknown error occurred');
                        }
                    } catch (e) {
                        showMessage('danger', 'Error parsing response');
                    }
                } else {
                    showMessage('danger', `HTTP Error: ${xhr.status}`);
                }
            }
        };
        
        xhr.send();
    }
}

// Reset user password via AJAX
function resetUserPassword(userId, userName) {
    if (confirm(`Are you sure you want to reset the password for ${userName}?`)) {
        showMessage('info', 'Generating new password...');
        
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `dashboard.php?page=users&ajax=reset_password&id=${userId}&token=${encodeURIComponent(csrfToken)}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            const passwordField = document.getElementById('newPassword');
                            const modal = document.getElementById('passwordResetModal');
                            
                            if (passwordField && modal) {
                                passwordField.value = data.new_password;
                                const bootstrapModal = new bootstrap.Modal(modal);
                                bootstrapModal.show();
                                showMessage('success', 'Password reset successfully!');
                            } else {
                                // Fallback if modal elements don't exist
                                alert(`Password reset successful!\n\nNew Password: ${data.new_password}\n\nPlease save this password securely.`);
                                showMessage('success', 'Password reset successfully! Password was shown in alert.');
                            }
                        } else {
                            showMessage('danger', data.message || 'Unknown error occurred');
                        }
                    } catch (e) {
                        showMessage('danger', 'Error parsing response');
                    }
                } else {
                    showMessage('danger', `HTTP Error: ${xhr.status}`);
                }
            }
        };
        
        xhr.send();
    }
}

// Copy password to clipboard
function copyPassword() {
    const passwordField = document.getElementById('newPassword');
    
    if (!passwordField || !passwordField.value || passwordField.value === 'Loading...') {
        showMessage('danger', 'No password to copy.');
        return;
    }
    
    passwordField.select();
    passwordField.setSelectionRange(0, 99999);
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(passwordField.value).then(() => {
            showMessage('success', 'Password copied to clipboard!');
        }).catch(() => {
            fallbackCopy();
        });
    } else {
        fallbackCopy();
    }
    
    function fallbackCopy() {
        try {
            const success = document.execCommand('copy');
            if (success) {
                showMessage('success', 'Password copied to clipboard!');
            } else {
                showMessage('warning', 'Copy failed. Please manually select and copy the password.');
            }
        } catch (err) {
            showMessage('warning', 'Copy not supported. Please manually select and copy the password.');
        }
    }
}

// Confirm delete user
function confirmDeleteUser(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteUserForm').action = `dashboard.php?page=users&action=delete&id=${userId}`;
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

// Enhanced message display function
function showMessage(type, message) {
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
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
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
});

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
</script>