<?php
// pages/roles.php - Complete Role Management System

// Check if user has admin permission
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access role management. Only super administrators can manage roles.</p>
          </div>';
    return;
}

// Get action and parameters
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$role_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get success/error messages from URL parameters
if (isset($_GET['success'])) {
    $message = sanitize_input($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = sanitize_input($_GET['error']);
}

// Available permissions for the system
$available_permissions = [
    'all' => 'Full System Access (Super Admin)',
    'manage_users' => 'Manage Users',
    'manage_roles' => 'Manage Roles & Permissions',
    'manage_students' => 'Manage Students',
    'view_students' => 'View Students',
    'manage_faculty' => 'Manage Faculty',
    'view_faculty' => 'View Faculty',
    'manage_courses' => 'Manage Courses',
    'view_courses' => 'View Courses',
    'grade_students' => 'Grade Students',
    'view_grades' => 'View Grades',
    'manage_departments' => 'Manage Departments',
    'view_reports' => 'View Reports',
    'generate_reports' => 'Generate Reports',
    'view_announcements' => 'View Announcements',
    'manage_announcements' => 'Manage Announcements',
    'view_profile' => 'View Own Profile',
    'edit_profile' => 'Edit Own Profile',
    'view_student_progress' => 'View Student Progress (Parents)',
    'system_settings' => 'System Settings'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_role($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_role($role_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_role($role_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=roles&error=' . urlencode($result['message']));
                    exit;
                }
                break;
        }
    }
}

// Get all roles for listing
function get_all_roles() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT r.*, 
                   COUNT(u.id) as user_count,
                   GROUP_CONCAT(DISTINCT u.username ORDER BY u.username SEPARATOR ', ') as users_sample
            FROM roles r 
            LEFT JOIN users u ON r.id = u.role_id 
            GROUP BY r.id
            ORDER BY 
                CASE r.name 
                    WHEN 'super_admin' THEN 1
                    WHEN 'head_of_department' THEN 2
                    WHEN 'faculty' THEN 3
                    WHEN 'parent' THEN 4
                    WHEN 'student' THEN 5
                    ELSE 6
                END, r.name
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get roles error: " . $e->getMessage());
        return [];
    }
}

// Get single role for editing
function get_role_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get role error: " . $e->getMessage());
        return false;
    }
}

// Handle add role
function handle_add_role($data) {
    global $pdo;
    
    // Validate input
    $errors = [];
    
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Role name must be at least 2 characters long.";
    }
    
    if (!preg_match('/^[a-z_]+$/', $data['name'])) {
        $errors[] = "Role name can only contain lowercase letters and underscores.";
    }
    
    if (empty($data['description'])) {
        $errors[] = "Role description is required.";
    }
    
    if (empty($data['permissions']) || !is_array($data['permissions'])) {
        $errors[] = "At least one permission must be selected.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate role name
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$data['name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Role name already exists.'];
        }
        
        // Insert new role
        $stmt = $pdo->prepare("
            INSERT INTO roles (name, description, permissions) 
            VALUES (?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'],
            json_encode($data['permissions'])
        ]);
        
        if ($result) {
            $new_role_id = $pdo->lastInsertId();
            
            // Log the action
            log_audit($_SESSION['user_id'], 'create_role', 'roles', $new_role_id, null, [
                'name' => $data['name'],
                'description' => $data['description'],
                'permissions' => $data['permissions']
            ]);
            
            return ['success' => true, 'message' => 'Role created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create role.'];
        }
        
    } catch (Exception $e) {
        error_log("Add role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Handle edit role
function handle_edit_role($id, $data) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid role ID.'];
    }
    
    // Prevent editing super_admin role
    $current_role = get_role_by_id($id);
    if ($current_role && $current_role['name'] === 'super_admin') {
        return ['success' => false, 'message' => 'Super admin role cannot be modified.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['description'])) {
        $errors[] = "Role description is required.";
    }
    
    if (empty($data['permissions']) || !is_array($data['permissions'])) {
        $errors[] = "At least one permission must be selected.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Get old values for audit
        $old_role = get_role_by_id($id);
        if (!$old_role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }
        
        // Update role (name is immutable for system roles)
        $stmt = $pdo->prepare("
            UPDATE roles 
            SET description = ?, permissions = ? 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['description'],
            json_encode($data['permissions']),
            $id
        ]);
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'update_role', 'roles', $id, $old_role, $data);
            
            return ['success' => true, 'message' => 'Role updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update role.'];
        }
        
    } catch (Exception $e) {
        error_log("Edit role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Handle delete role
function handle_delete_role($id) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid role ID.'];
    }
    
    try {
        // Get role info for validation
        $role = get_role_by_id($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }
        
        // Prevent deleting system roles
        $system_roles = ['super_admin', 'head_of_department', 'faculty', 'parent', 'student'];
        if (in_array($role['name'], $system_roles)) {
            return ['success' => false, 'message' => 'System roles cannot be deleted.'];
        }
        
        // Check if role is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['user_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete role: ' . $result['user_count'] . ' users are assigned to this role.'];
        }
        
        // Delete role
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            log_audit($_SESSION['user_id'], 'delete_role', 'roles', $id, $role, null);
            
            return ['success' => true, 'message' => 'Role deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete role.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get data based on current action
$roles = [];
$edit_role = null;

if ($action === 'list' || $action === 'delete') {
    $roles = get_all_roles();
}

if ($action === 'edit' && $role_id) {
    $edit_role = get_role_by_id($role_id);
    if (!$edit_role) {
        $error = 'Role not found.';
        $action = 'list';
        $roles = get_all_roles();
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
<!-- Add Role Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Role</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=roles&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Role Name</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="e.g., librarian, accountant"
                               pattern="[a-z_]+"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        <div class="form-hint">Use lowercase letters and underscores only. This cannot be changed later.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Display Name</label>
                        <input type="text" name="description" class="form-control" required 
                               placeholder="e.g., Librarian, Accountant"
                               value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
                        <div class="form-hint">Human-readable name for this role.</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <div class="row">
                    <?php foreach ($available_permissions as $perm => $desc): ?>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-check">
                            <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input"
                                   <?php echo (isset($_POST['permissions']) && in_array($perm, $_POST['permissions'])) ? 'checked' : ''; ?>>
                            <span class="form-check-label">
                                <strong><?php echo htmlspecialchars($desc); ?></strong>
                                <small class="text-muted d-block"><?php echo $perm; ?></small>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint">Select all permissions this role should have. "Full System Access" grants all permissions.</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Create Role
                </button>
                <a href="dashboard.php?page=roles" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_role): ?>
<!-- Edit Role Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Role: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $edit_role['name']))); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($edit_role['name'] === 'super_admin'): ?>
        <div class="alert alert-warning">
            <h4>System Role</h4>
            <p>The super admin role cannot be modified as it's a core system role with full access.</p>
        </div>
        <a href="dashboard.php?page=roles" class="btn btn-secondary">Back to Roles</a>
        <?php else: ?>
        <form method="POST" action="dashboard.php?page=roles&action=edit&id=<?php echo $edit_role['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_role['name']); ?>" readonly>
                        <div class="form-hint">Role names cannot be changed after creation.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Display Name</label>
                        <input type="text" name="description" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['description'] ?? $edit_role['description']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <?php 
                $current_permissions = json_decode($edit_role['permissions'], true) ?: [];
                $selected_permissions = $_POST['permissions'] ?? $current_permissions;
                ?>
                <div class="row">
                    <?php foreach ($available_permissions as $perm => $desc): ?>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-check">
                            <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input"
                                   <?php echo in_array($perm, $selected_permissions) ? 'checked' : ''; ?>>
                            <span class="form-check-label">
                                <strong><?php echo htmlspecialchars($desc); ?></strong>
                                <small class="text-muted d-block"><?php echo $perm; ?></small>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Role
                </button>
                <a href="dashboard.php?page=roles" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Roles List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Add Role
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                    <?php
                    $permissions = json_decode($role['permissions'], true) ?: [];
                    $is_system_role = in_array($role['name'], ['super_admin', 'head_of_department', 'faculty', 'parent', 'student']);
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($is_system_role): ?>
                                    <span class="avatar bg-blue text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 1l3 6 6 .75-4.12 4.62L17.75 19 12 16l-5.75 3 .87-6.63L3 7.75 9 7z"/>
                                        </svg>
                                    </span>
                                    <?php else: ?>
                                    <span class="avatar bg-green text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                        </svg>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                                        <?php if ($is_system_role): ?>
                                            <span class="badge bg-blue-lt ms-1">System</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($role['name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">
                            <?php echo htmlspecialchars($role['description']); ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (in_array('all', $permissions)): ?>
                                    <span class="badge bg-red">Full Access</span>
                                <?php else: ?>
                                    <?php 
                                    $permission_count = count($permissions);
                                    if ($permission_count <= 3): 
                                        foreach (array_slice($permissions, 0, 3) as $perm):
                                    ?>
                                        <span class="badge bg-blue-lt"><?php echo htmlspecialchars($available_permissions[$perm] ?? $perm); ?></span>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <span class="badge bg-blue-lt"><?php echo $permission_count; ?> permissions</span>
                                        <button class="btn btn-sm btn-ghost-secondary" data-bs-toggle="tooltip" 
                                                title="<?php echo htmlspecialchars(implode(', ', array_map(fn($p) => $available_permissions[$p] ?? $p, $permissions))); ?>">
                                            View All
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($role['user_count'] > 0): ?>
                                <span class="badge bg-green"><?php echo $role['user_count']; ?> users</span>
                                <?php if ($role['users_sample']): ?>
                                    <div class="text-muted small mt-1">
                                        <?php 
                                        $users = explode(', ', $role['users_sample']);
                                        echo htmlspecialchars(implode(', ', array_slice($users, 0, 3)));
                                        if (count($users) > 3) echo '...';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No users</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($role['created_at'])); ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=roles&action=edit&id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <?php if (!$is_system_role): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=roles&action=edit&id=<?php echo $role['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit Role
                                        </a>
                                        
                                        <?php if ($role['user_count'] == 0): ?>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete Role
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="badge bg-blue-lt">System</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No roles found. <a href="dashboard.php?page=roles&action=add">Create the first custom role</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Role Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count($roles); ?></div>
                <div class="d-flex mb-2">
                    <div>All system roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">System Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => in_array($r['name'], ['super_admin', 'head_of_department', 'faculty', 'parent', 'student']))); ?></div>
                <div class="d-flex mb-2">
                    <div>Built-in roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Custom Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => !in_array($r['name'], ['super_admin', 'head_of_department', 'faculty', 'parent', 'student']))); ?></div>
                <div class="d-flex mb-2">
                    <div>User-created roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Assigned Users</div>
                </div>
                <div class="h1 mb-3"><?php echo array_sum(array_column($roles, 'user_count')); ?></div>
                <div class="d-flex mb-2">
                    <div>Total role assignments</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete role <strong id="deleteRoleName"></strong>? This action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteRoleForm" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="btn btn-danger">Yes, delete role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, 'dashboard.php?page=roles');
}

// Confirm delete role
function confirmDeleteRole(roleId, roleName) {
    document.getElementById('deleteRoleName').textContent = roleName;
    document.getElementById('deleteRoleForm').action = `dashboard.php?page=roles&action=delete&id=${roleId}`;
    const modal = new bootstrap.Modal(document.getElementById('deleteRoleModal'));
    modal.show();
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Check if at least one permission is selected
            const permissionCheckboxes = form.querySelectorAll('input[name="permissions[]"]');
            if (permissionCheckboxes.length > 0) {
                const checkedPermissions = form.querySelectorAll('input[name="permissions[]"]:checked');
                if (checkedPermissions.length === 0) {
                    e.preventDefault();
                    showMessage('danger', 'Please select at least one permission for this role.');
                    return;
                }
            }
            
            // Validate required fields
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
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Show message function
function showMessage(type, message) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.alert');
    existingMessages.forEach(msg => {
        if (msg.classList.contains('alert-dismissible')) {
            msg.remove();
        }
    });
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.page-body .container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv && alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}
</script>