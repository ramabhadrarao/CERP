<?php
// pages/users.php - Enhanced User Management with Tabler UI Integration

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

// Pagination settings
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Get success/error messages from URL parameters
$message = '';
$error = '';
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
        }
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

// Get users with enhanced filtering and pagination
function get_users_with_filters($search, $role_filter, $status_filter, $offset, $per_page) {
    global $pdo;
    
    $where_conditions = [];
    $params = [];
    
    // Search functionality
    if (!empty($search)) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    // Role filter
    if (!empty($role_filter)) {
        $where_conditions[] = "r.name = :role";
        $params['role'] = $role_filter;
    }
    
    // Status filter
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = :status";
        $params['status'] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    try {
        // Get total count
        $count_sql = "
            SELECT COUNT(*) as total 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            {$where_clause}
        ";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Get users
        $sql = "
            SELECT u.*, r.name as role_name, r.description as role_description,
                   DATE_FORMAT(u.created_at, '%M %d, %Y') as formatted_created_at,
                   DATE_FORMAT(u.last_login, '%M %d, %Y %h:%i %p') as formatted_last_login
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            {$where_clause}
            ORDER BY u.created_at DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        return ['users' => $users, 'total' => $total];
        
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return ['users' => [], 'total' => 0];
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

// Handle add user
function handle_add_user($data) {
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
    
    if (empty($data['role_id'])) {
        $errors[] = "Role selection is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email address already exists.'];
        }
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
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
                'role_id' => $data['role_id'],
                'status' => $data['status'] ?? 'active'
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

// Handle edit user
function handle_edit_user($id, $data) {
    global $pdo;
    
    if (!$id || $id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot modify this user.'];
    }
    
    // Get current user
    $current_user = get_user_by_id($id);
    if (!$current_user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }
    
    if (!empty($data['password']) && strlen($data['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate username (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$data['username'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Check for duplicate email (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email address already exists.'];
        }
        
        // Update user
        if (!empty($data['password'])) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, password_hash = ?, role_id = ?, 
                    first_name = ?, last_name = ?, phone = ?, address = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['role_id'],
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['status'],
                $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role_id = ?, 
                    first_name = ?, last_name = ?, phone = ?, address = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['username'],
                $data['email'],
                $data['role_id'],
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['status'],
                $id
            ]);
        }
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'update_user', 'users', $id, $current_user, $data);
            
            return ['success' => true, 'message' => 'User updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user.'];
        }
        
    } catch (Exception $e) {
        error_log("Edit user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Handle delete user
function handle_delete_user($id) {
    global $pdo;
    
    if (!$id || $id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot delete this user.'];
    }
    
    try {
        // Get user info for audit
        $user = get_user_by_id($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Check if user is a super admin (prevent deleting other super admins)
        if ($user['role_name'] === 'super_admin') {
            return ['success' => false, 'message' => 'Cannot delete super administrator accounts.'];
        }
        
        // Delete user
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
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Get data based on current action
$roles = get_all_roles();
$users_data = ['users' => [], 'total' => 0];
$edit_user = null;

if ($action === 'list' || $action === 'delete') {
    $users_data = get_users_with_filters($search, $role_filter, $status_filter, $offset, $per_page);
}

if ($action === 'edit' && $user_id) {
    $edit_user = get_user_by_id($user_id);
    if (!$edit_user) {
        $error = 'User not found.';
        $action = 'list';
        $users_data = get_users_with_filters($search, $role_filter, $status_filter, $offset, $per_page);
    }
}

// Calculate pagination
$total_pages = ceil($users_data['total'] / $per_page);
?>

<!-- Messages -->
<?php if ($message): ?>
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

<?php if ($error): ?>
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
        <form method="POST" action="dashboard.php?page=users&action=add" id="userForm">
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
                        <input type="text" name="username" class="form-control" required minlength="3"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <div class="form-hint">Must be at least 3 characters long and unique.</div>
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
                        <label class="form-label required">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="form-hint">Must be at least 8 characters long.</div>
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
                                <?php echo htmlspecialchars($role['description']); ?>
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
        <form method="POST" action="dashboard.php?page=users&action=edit&id=<?php echo $edit_user['id']; ?>" id="userForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
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
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required minlength="3"
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
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="form-hint">Leave blank to keep current password.</div>
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
                                <?php echo htmlspecialchars($role['description']); ?>
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
            <a href="dashboard.php?page=users&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Add User
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card-body border-bottom py-3">
        <form method="GET" action="dashboard.php" class="d-flex">
            <input type="hidden" name="page" value="users">
            <div class="d-flex">
                <div class="text-muted me-3">
                    <div class="ms-2 d-none d-md-block">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search users..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="text-muted me-3">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['name']; ?>" <?php echo ($role_filter === $role['name']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role['description']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="text-muted me-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="text-muted">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="dashboard.php?page=users" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users_data['users'])): ?>
                    <?php foreach ($users_data['users'] as $user_item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar me-3"><?php echo strtoupper(substr($user_item['first_name'], 0, 1)); ?></span>
                                <div>
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?>
                                    </div>
                                    <div class="text-muted">@<?php echo htmlspecialchars($user_item['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div><?php echo htmlspecialchars($user_item['email']); ?></div>
                                <?php if ($user_item['phone']): ?>
                                <div class="text-muted small"><?php echo htmlspecialchars($user_item['phone']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-blue">
                                <?php echo htmlspecialchars($user_item['role_description'] ?: ucfirst(str_replace('_', ' ', $user_item['role_name']))); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_class = match($user_item['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                'suspended' => 'bg-red',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($user_item['status']); ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?php echo $user_item['formatted_created_at']; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo $user_item['formatted_last_login'] ?: 'Never'; ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=users&action=edit&id=<?php echo $user_item['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=users&action=edit&id=<?php echo $user_item['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit User
                                        </a>
                                        
                                        <button class="dropdown-item" onclick="toggleUserStatus(<?php echo $user_item['id']; ?>, '<?php echo $user_item['status']; ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="1" y="3" width="15" height="13"></rect>
                                                <path d="m16 8l5-3-5-3v6"></path>
                                            </svg>
                                            <?php echo $user_item['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                        
                                        <button class="dropdown-item" onclick="resetUserPassword(<?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['username']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <circle cx="12" cy="16" r="1"></circle>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                            Reset Password
                                        </button>
                                        
                                        <?php if ($user_item['id'] != $_SESSION['user_id'] && $user_item['role_name'] !== 'super_admin'): ?>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteUser(<?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['username']); ?>')">
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
                            No users found. <a href="dashboard.php?page=users&action=add">Create the first user</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">
            Showing <span><?php echo $offset + 1; ?></span> to <span><?php echo min($offset + $per_page, $users_data['total']); ?></span>
            of <span><?php echo $users_data['total']; ?></span> entries
        </p>
        <ul class="pagination m-0 ms-auto">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&p=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    prev
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=users&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&p=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                    next
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
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
                <div class="h1 mb-3"><?php echo number_format($users_data['total']); ?></div>
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
                <div class="h1 mb-3">
                    <?php 
                    $active_count = array_filter($users_data['users'], fn($u) => $u['status'] === 'active');
                    echo count($active_count);
                    ?>
                </div>
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
                    <div class="subheader">New This Month</div>
                </div>
                <div class="h1 mb-3">
                    <?php 
                    $this_month = array_filter($users_data['users'], fn($u) => date('Y-m', strtotime($u['created_at'])) === date('Y-m'));
                    echo count($this_month);
                    ?>
                </div>
                <div class="d-flex mb-2">
                    <div>Registered this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Online Today</div>
                </div>
                <div class="h1 mb-3">
                    <?php 
                    $today = array_filter($users_data['users'], fn($u) => $u['last_login'] && date('Y-m-d', strtotime($u['last_login'])) === date('Y-m-d'));
                    echo count($today);
                    ?>
                </div>
                <div class="d-flex mb-2">
                    <div>Logged in today</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, 'dashboard.php?page=users');
}

// Toggle password visibility
function togglePassword(button) {
    const input = button.previousElementSibling;
    const icon = button.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    }
}

// AJAX User Operations
function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    createTablerPopup(`${action.charAt(0).toUpperCase() + action.slice(1)} User`, `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <p>Processing user status change...</p>
        </div>
    `);

    fetch(`ajax_debug.php?action=toggle_status&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTablerPopup('Status Updated', `
                    <div class="alert alert-success">
                        <div class="d-flex">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">Success!</h4>
                                <div class="text-muted">${data.message}</div>
                                <div class="mt-2"><strong>New Status:</strong> <span class="badge bg-${data.new_status === 'active' ? 'green' : 'gray'}">${data.new_status}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="btn-list mt-3">
                        <button class="btn btn-primary" onclick="location.reload()">Refresh Page</button>
                        <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
                    </div>
                `);
            } else {
                updateTablerPopup('Error', `
                    <div class="alert alert-danger">
                        <h4>Error</h4>
                        <p>${data.message}</p>
                    </div>
                    <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
                `);
            }
        })
        .catch(error => {
            updateTablerPopup('Network Error', `
                <div class="alert alert-danger">
                    <h4>Connection Failed</h4>
                    <p>Could not connect to server: ${error.message}</p>
                </div>
                <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
            `);
        });
}

function resetUserPassword(userId, username) {
    createTablerPopup('Reset Password', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-warning" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <circle cx="12" cy="16" r="1"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h3>Reset Password</h3>
            <p class="text-muted">Are you sure you want to reset the password for user <strong>${username}</strong>?<br>A new random password will be generated.</p>
            <div class="btn-list">
                <button class="btn btn-warning" onclick="confirmResetPassword(${userId})">Yes, reset password</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function confirmResetPassword(userId) {
    updateTablerPopup('Resetting Password', `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <p>Generating new password...</p>
        </div>
    `);

    fetch(`ajax_debug.php?action=reset_password&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTablerPopup('Password Reset', `
                    <div class="alert alert-success">
                        <div class="d-flex">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">Password Reset Successfully!</h4>
                                <div class="text-muted">${data.message}</div>
                                <div class="mt-3 p-3 bg-yellow-lt rounded">
                                    <strong>New Password:</strong> 
                                    <code class="fs-4">${data.new_password}</code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('${data.new_password}')">Copy</button>
                                </div>
                                <div class="mt-2 text-warning"><small>⚠️ Please save this password securely. It will not be shown again.</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="btn-list mt-3">
                        <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
                    </div>
                `);
            } else {
                updateTablerPopup('Reset Failed', `
                    <div class="alert alert-danger">
                        <h4>Error</h4>
                        <p>${data.message}</p>
                    </div>
                    <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
                `);
            }
        })
        .catch(error => {
            updateTablerPopup('Network Error', `
                <div class="alert alert-danger">
                    <h4>Connection Failed</h4>
                    <p>Could not connect to server: ${error.message}</p>
                </div>
                <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
            `);
        });
}

async function confirmDeleteUser(userId, username) {
    const confirmed = await confirmDelete('user', username);
    if (confirmed) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `dashboard.php?page=users&action=delete&id=${userId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?php echo generate_csrf_token(); ?>';
        
        form.appendChild(csrfToken);
        document.body.appendChild(form);
        form.submit();
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showMessage('success', 'Password copied to clipboard!');
    }).catch(() => {
        showMessage('warning', 'Could not copy to clipboard. Please copy manually.');
    });
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            // Basic validation
            const requiredFields = userForm.querySelectorAll('[required]');
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
                return;
            }
            
            // Email validation
            const emailField = userForm.querySelector('input[type="email"]');
            if (emailField && !emailField.validity.valid) {
                e.preventDefault();
                showMessage('danger', 'Please enter a valid email address.');
                emailField.classList.add('is-invalid');
                return;
            }
            
            // Password validation
            const passwordField = userForm.querySelector('input[name="password"]');
            if (passwordField && passwordField.value && passwordField.value.length < 8) {
                e.preventDefault();
                showMessage('danger', 'Password must be at least 8 characters long.');
                passwordField.classList.add('is-invalid');
                return;
            }
        });
        
        // Remove invalid class when user starts typing
        const inputs = userForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    }
});
</script>