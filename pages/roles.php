<?php
// pages/roles.php - Dynamic Role Management System

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

// Initialize variables
$message = '';
$error = '';

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
                
            case 'add_permission':
                $result = handle_add_permission($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Get all existing permissions from all roles dynamically
function get_all_available_permissions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT permissions FROM roles WHERE permissions IS NOT NULL AND permissions != ''");
        $all_permissions = [];
        
        while ($row = $stmt->fetch()) {
            $permissions = json_decode($row['permissions'], true);
            if (is_array($permissions)) {
                $all_permissions = array_merge($all_permissions, $permissions);
            }
        }
        
        // Remove duplicates and sort
        $all_permissions = array_unique($all_permissions);
        sort($all_permissions);
        
        return $all_permissions;
    } catch (Exception $e) {
        error_log("Get permissions error: " . $e->getMessage());
        return [];
    }
}

// Get permission categories based on existing permissions
function get_permission_categories($all_permissions) {
    $categories = [];
    
    foreach ($all_permissions as $permission) {
        // Auto-categorize based on permission name patterns
        if (strpos($permission, 'manage_users') !== false || strpos($permission, 'manage_roles') !== false || strpos($permission, 'system_') !== false || $permission === 'all') {
            $categories['System Administration'][] = $permission;
        } elseif (strpos($permission, 'student') !== false) {
            $categories['Student Management'][] = $permission;
        } elseif (strpos($permission, 'faculty') !== false) {
            $categories['Faculty Management'][] = $permission;
        } elseif (strpos($permission, 'course') !== false || strpos($permission, 'elective') !== false) {
            $categories['Course Management'][] = $permission;
        } elseif (strpos($permission, 'grade') !== false || strpos($permission, 'mark') !== false || strpos($permission, 'assessment') !== false) {
            $categories['Assessment & Marks'][] = $permission;
        } elseif (strpos($permission, 'attendance') !== false) {
            $categories['Attendance Management'][] = $permission;
        } elseif (strpos($permission, 'report') !== false || strpos($permission, 'analytics') !== false || strpos($permission, 'export') !== false) {
            $categories['Reporting & Analytics'][] = $permission;
        } elseif (strpos($permission, 'department') !== false || strpos($permission, 'program') !== false || strpos($permission, 'batch') !== false) {
            $categories['Institutional Management'][] = $permission;
        } elseif (strpos($permission, 'profile') !== false || strpos($permission, 'password') !== false) {
            $categories['Personal Access'][] = $permission;
        } elseif (strpos($permission, 'ward') !== false || strpos($permission, 'parent') !== false) {
            $categories['Parent Access'][] = $permission;
        } elseif (strpos($permission, 'own_') !== false || strpos($permission, 'view_fee') !== false || strpos($permission, 'download_') !== false) {
            $categories['Student Access'][] = $permission;
        } elseif (strpos($permission, 'notification') !== false || strpos($permission, 'announcement') !== false || strpos($permission, 'communication') !== false) {
            $categories['Communications'][] = $permission;
        } else {
            $categories['Other Permissions'][] = $permission;
        }
    }
    
    return $categories;
}

// Get all roles for listing with enhanced query
function get_all_roles() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT r.*, 
                   COUNT(u.id) as user_count,
                   GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) ORDER BY u.first_name SEPARATOR ', ') as users_sample
            FROM roles r 
            LEFT JOIN users u ON r.id = u.role_id 
            GROUP BY r.id
            ORDER BY 
                CASE 
                    WHEN r.is_system_role = 1 THEN 1
                    ELSE 2
                END,
                r.name
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

// Enhanced handle add role function
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
    
    // Check if it's a system role name
    $system_role_names = ['super_admin', 'admin', 'principal', 'hod', 'faculty', 'student', 'parent', 'staff', 'guest'];
    if (in_array($data['name'], $system_role_names)) {
        $errors[] = "Cannot create custom role with system role name.";
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
            INSERT INTO roles (name, description, permissions, is_system_role, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'],
            json_encode($data['permissions']),
            0, // Custom roles are not system roles
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            $new_role_id = $pdo->lastInsertId();
            
            // Log the action
            log_audit($_SESSION['user_id'], 'create_role', 'roles', $new_role_id, null, [
                'name' => $data['name'],
                'description' => $data['description'],
                'permissions' => $data['permissions'],
                'is_system_role' => 0
            ]);
            
            return ['success' => true, 'message' => 'Role created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create role.'];
        }
        
    } catch (Exception $e) {
        error_log("Add role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle edit role function
function handle_edit_role($id, $data) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid role ID.'];
    }
    
    // Get current role to check if it's a system role
    $current_role = get_role_by_id($id);
    if (!$current_role) {
        return ['success' => false, 'message' => 'Role not found.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['description'])) {
        $errors[] = "Role description is required.";
    }
    
    if (empty($data['permissions']) || !is_array($data['permissions'])) {
        $errors[] = "At least one permission must be selected.";
    }
    
    // For custom roles, validate name if provided
    if (!$current_role['is_system_role'] && !empty($data['name'])) {
        if (strlen($data['name']) < 2) {
            $errors[] = "Role name must be at least 2 characters long.";
        }
        
        if (!preg_match('/^[a-z_]+$/', $data['name'])) {
            $errors[] = "Role name can only contain lowercase letters and underscores.";
        }
        
        // Check for duplicate name (excluding current role)
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ? AND id != ?");
        $stmt->execute([$data['name'], $id]);
        if ($stmt->fetch()) {
            $errors[] = "Role name already exists.";
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Update role (name is immutable for system roles)
        if ($current_role['is_system_role']) {
            $stmt = $pdo->prepare("
                UPDATE roles 
                SET description = ?, permissions = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['description'],
                json_encode($data['permissions']),
                $data['status'] ?? $current_role['status'],
                $id
            ]);
        } else {
            // For custom roles, allow name changes
            $stmt = $pdo->prepare("
                UPDATE roles 
                SET name = ?, description = ?, permissions = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['name'] ?? $current_role['name'],
                $data['description'],
                json_encode($data['permissions']),
                $data['status'] ?? $current_role['status'],
                $id
            ]);
        }
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'update_role', 'roles', $id, $current_role, $data);
            
            return ['success' => true, 'message' => 'Role updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update role.'];
        }
        
    } catch (Exception $e) {
        error_log("Edit role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle delete role function
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
        if ($role['is_system_role']) {
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
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Handle adding new permission
function handle_add_permission($data) {
    if (empty($data['new_permission']) || strlen($data['new_permission']) < 3) {
        return ['success' => false, 'message' => 'Permission name must be at least 3 characters long.'];
    }
    
    if (!preg_match('/^[a-z_]+$/', $data['new_permission'])) {
        return ['success' => false, 'message' => 'Permission name can only contain lowercase letters and underscores.'];
    }
    
    // Check if permission already exists
    $existing_permissions = get_all_available_permissions();
    if (in_array($data['new_permission'], $existing_permissions)) {
        return ['success' => false, 'message' => 'Permission already exists.'];
    }
    
    // Log the action for audit
    log_audit($_SESSION['user_id'], 'add_permission', 'permissions', null, null, [
        'permission' => $data['new_permission'],
        'description' => $data['permission_description'] ?? ''
    ]);
    
    return ['success' => true, 'message' => 'Permission "' . $data['new_permission'] . '" is now available for use in roles.'];
}

// Get data based on current action
$roles = [];
$edit_role = null;
$all_permissions = get_all_available_permissions();
$permission_categories = get_permission_categories($all_permissions);

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

// Helper function to get permission display name
function get_permission_display_name($permission) {
    return ucwords(str_replace('_', ' ', $permission));
}
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
        <form method="POST" action="dashboard.php?page=roles&action=add" id="roleForm">
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
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <div class="card">
                    <div class="card-body">
                        <!-- Search permissions -->
                        <div class="mb-3">
                            <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions...">
                        </div>
                        
                        <!-- Add new permission section -->
                        <div class="alert alert-info">
                            <h5>Add New Permission</h5>
                            <div class="input-group">
                                <input type="text" id="newPermissionInput" class="form-control" placeholder="e.g., manage_library" pattern="[a-z_]+">
                                <button type="button" class="btn btn-success" onclick="addNewPermission()">Add Permission</button>
                            </div>
                            <small class="form-hint">Use lowercase letters and underscores only. New permissions will be available immediately.</small>
                        </div>
                        
                        <!-- Dynamic permission categories -->
                        <?php if (!empty($permission_categories)): ?>
                            <?php foreach ($permission_categories as $category => $perms): ?>
                            <div class="mb-4 permission-category">
                                <div class="d-flex align-items-center mb-3">
                                    <h5 class="mb-0 me-3"><?php echo htmlspecialchars($category); ?></h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input category-toggle" type="checkbox" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                        <label class="form-check-label text-muted">Select All</label>
                                    </div>
                                </div>
                                <div class="row" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                    <?php foreach ($perms as $perm): ?>
                                    <div class="col-md-6 col-lg-4 permission-item">
                                        <label class="form-check">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input permission-checkbox"
                                                   <?php echo (isset($_POST['permissions']) && in_array($perm, $_POST['permissions'])) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">
                                                <strong><?php echo htmlspecialchars(get_permission_display_name($perm)); ?></strong>
                                                <small class="text-muted d-block"><?php echo $perm; ?></small>
                                            </span>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <h5>No Existing Permissions Found</h5>
                                <p>Start by adding your first permission using the form above, or check existing roles for permissions.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
        <h3 class="card-title">Edit Role: <?php echo htmlspecialchars($edit_role['description']); ?></h3>
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
        <form method="POST" action="dashboard.php?page=roles&action=edit&id=<?php echo $edit_role['id']; ?>" id="roleForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <?php if ($edit_role['is_system_role']): ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_role['name']); ?>" readonly>
                            <div class="form-hint">System role names cannot be changed.</div>
                        <?php else: ?>
                            <input type="text" name="name" class="form-control" 
                                   pattern="[a-z_]+"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $edit_role['name']); ?>">
                            <div class="form-hint">Use lowercase letters and underscores only.</div>
                        <?php endif; ?>
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
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo (($_POST['status'] ?? $edit_role['status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (($_POST['status'] ?? $edit_role['status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <?php 
                $current_permissions = json_decode($edit_role['permissions'], true) ?: [];
                $selected_permissions = $_POST['permissions'] ?? $current_permissions;
                ?>
                
                <div class="card">
                    <div class="card-body">
                        <!-- Search permissions -->
                        <div class="mb-3">
                            <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions...">
                        </div>
                        
                        <!-- Add new permission section -->
                        <div class="alert alert-info">
                            <h5>Add New Permission</h5>
                            <div class="input-group">
                                <input type="text" id="newPermissionInput" class="form-control" placeholder="e.g., manage_library" pattern="[a-z_]+">
                                <button type="button" class="btn btn-success" onclick="addNewPermission()">Add Permission</button>
                            </div>
                            <small class="form-hint">Use lowercase letters and underscores only. New permissions will be available immediately.</small>
                        </div>
                        
                        <!-- Dynamic permission categories -->
                        <?php foreach ($permission_categories as $category => $perms): ?>
                        <div class="mb-4 permission-category">
                            <div class="d-flex align-items-center mb-3">
                                <h5 class="mb-0 me-3"><?php echo htmlspecialchars($category); ?></h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input category-toggle" type="checkbox" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                    <label class="form-check-label text-muted">Select All</label>
                                </div>
                            </div>
                            <div class="row" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                <?php foreach ($perms as $perm): ?>
                                <div class="col-md-6 col-lg-4 permission-item">
                                    <label class="form-check">
                                        <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input permission-checkbox"
                                               <?php echo in_array($perm, $selected_permissions) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">
                                            <strong><?php echo htmlspecialchars(get_permission_display_name($perm)); ?></strong>
                                            <small class="text-muted d-block"><?php echo $perm; ?></small>
                                        </span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
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
    </div>
</div>

<?php else: ?>
<!-- Enhanced Roles List -->
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
                    <th>Type</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                    <?php
                    $permissions = json_decode($role['permissions'], true) ?: [];
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($role['is_system_role']): ?>
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
                                        <?php echo htmlspecialchars($role['description']); ?>
                                    </div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($role['name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">
                            <?php echo htmlspecialchars($role['description']); ?>
                        </td>
                        <td>
                            <?php if ($role['is_system_role']): ?>
                                <span class="badge bg-blue">System Role</span>
                            <?php else: ?>
                                <span class="badge bg-green">Custom Role</span>
                            <?php endif; ?>
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
                                        <span class="badge bg-blue-lt"><?php echo htmlspecialchars(get_permission_display_name($perm)); ?></span>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <span class="badge bg-blue-lt"><?php echo $permission_count; ?> permissions</span>
                                        <button class="btn btn-sm btn-ghost-secondary" onclick="showPermissionDetails('<?php echo htmlspecialchars(json_encode($permissions)); ?>')">
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
                                        echo htmlspecialchars(implode(', ', array_slice($users, 0, 2)));
                                        if (count($users) > 2) echo '...';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No users</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_class = match($role['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($role['status']); ?>
                            </span>
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
                                
                                <?php if (!$role['is_system_role'] && $role['user_count'] == 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                                <?php elseif (!$role['is_system_role']): ?>
                                <span class="badge bg-red-lt">In Use</span>
                                <?php else: ?>
                                <span class="badge bg-blue-lt">System</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No roles found. <a href="dashboard.php?page=roles&action=add">Create the first custom role</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Enhanced Role Statistics -->
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
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => $r['is_system_role'])); ?></div>
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
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => !$r['is_system_role'])); ?></div>
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
                    <div class="subheader">Available Permissions</div>
                </div>
                <div class="h1 mb-3"><?php echo count($all_permissions); ?></div>
                <div class="d-flex mb-2">
                    <div>Unique permissions</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Enhanced role management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize category toggles
    initializeCategoryToggles();
    
    // Initialize permission search
    initializePermissionSearch();
    
    // Initialize form validation
    initializeFormValidation();
});

function initializeCategoryToggles() {
    const categoryToggles = document.querySelectorAll('.category-toggle');
    categoryToggles.forEach(toggle => {
        const categoryName = toggle.dataset.category;
        const categoryDiv = document.querySelector(`[data-category="${categoryName}"]`);
        
        if (categoryDiv) {
            const checkboxes = categoryDiv.querySelectorAll('.permission-checkbox');
            
            // Set initial state
            updateToggleState(toggle, checkboxes);
            
            // Handle toggle change
            toggle.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
            
            // Update toggle when individual checkboxes change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateToggleState(toggle, checkboxes);
                });
            });
        }
    });
}

function updateToggleState(toggle, checkboxes) {
    const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;
    
    if (checkedCount === 0) {
        toggle.checked = false;
        toggle.indeterminate = false;
    } else if (checkedCount === totalCount) {
        toggle.checked = true;
        toggle.indeterminate = false;
    } else {
        toggle.checked = false;
        toggle.indeterminate = true;
    }
}

function initializePermissionSearch() {
    const searchInput = document.getElementById('permissionSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const permissionItems = document.querySelectorAll('.permission-item');
            const categories = document.querySelectorAll('.permission-category');
            
            permissionItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                item.style.display = shouldShow ? 'block' : 'none';
            });
            
            // Hide categories with no visible permissions
            categories.forEach(category => {
                const visibleItems = category.querySelectorAll('.permission-item:not([style*="display: none"])');
                category.style.display = visibleItems.length > 0 ? 'block' : 'none';
            });
        });
    }
}

function initializeFormValidation() {
    const roleForm = document.getElementById('roleForm');
    if (roleForm) {
        roleForm.addEventListener('submit', function(e) {
            const permissionCheckboxes = roleForm.querySelectorAll('input[name="permissions[]"]');
            if (permissionCheckboxes.length > 0) {
                const checkedPermissions = roleForm.querySelectorAll('input[name="permissions[]"]:checked');
                if (checkedPermissions.length === 0) {
                    e.preventDefault();
                    showMessage('danger', 'Please select at least one permission for this role.');
                    return;
                }
            }
        });
    }
}

function addNewPermission() {
    const input = document.getElementById('newPermissionInput');
    const permission = input.value.trim();
    
    if (!permission) {
        showMessage('warning', 'Please enter a permission name.');
        return;
    }
    
    if (!/^[a-z_]+$/.test(permission)) {
        showMessage('danger', 'Permission name can only contain lowercase letters and underscores.');
        return;
    }
    
    // Check if permission already exists
    const existingCheckboxes = document.querySelectorAll('input[name="permissions[]"]');
    const existingPermissions = Array.from(existingCheckboxes).map(cb => cb.value);
    
    if (existingPermissions.includes(permission)) {
        showMessage('warning', 'Permission already exists.');
        return;
    }
    
    // Add to "Other Permissions" category or create new category
    let otherCategory = document.querySelector('[data-category="other_permissions"]');
    if (!otherCategory) {
        // Create new category
        const categoriesContainer = document.querySelector('.card-body');
        const newCategoryHTML = `
            <div class="mb-4 permission-category">
                <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0 me-3">Custom Permissions</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input category-toggle" type="checkbox" data-category="custom_permissions">
                        <label class="form-check-label text-muted">Select All</label>
                    </div>
                </div>
                <div class="row" data-category="custom_permissions">
                </div>
            </div>
        `;
        categoriesContainer.insertAdjacentHTML('beforeend', newCategoryHTML);
        otherCategory = document.querySelector('[data-category="custom_permissions"]');
        
        // Reinitialize toggles for new category
        initializeCategoryToggles();
    }
    
    // Add the new permission
    const permissionHTML = `
        <div class="col-md-6 col-lg-4 permission-item">
            <label class="form-check">
                <input type="checkbox" name="permissions[]" value="${permission}" class="form-check-input permission-checkbox" checked>
                <span class="form-check-label">
                    <strong>${permission.charAt(0).toUpperCase() + permission.slice(1).replace(/_/g, ' ')}</strong>
                    <small class="text-muted d-block">${permission}</small>
                </span>
            </label>
        </div>
    `;
    
    otherCategory.insertAdjacentHTML('beforeend', permissionHTML);
    
    // Clear input
    input.value = '';
    
    // Show success message
    showMessage('success', `Permission "${permission}" added successfully!`);
    
    // Reinitialize functionality for new elements
    initializeCategoryToggles();
    initializePermissionSearch();
}

function showPermissionDetails(permissionsJson) {
    try {
        const permissions = JSON.parse(permissionsJson);
        const permissionList = permissions.map(p => 
            `<span class="badge bg-blue-lt me-1 mb-1">${p.charAt(0).toUpperCase() + p.slice(1).replace(/_/g, ' ')}</span>`
        ).join('');
        
        createTablerPopup('Role Permissions', `
            <div class="mb-3">
                <h5>All Permissions (${permissions.length})</h5>
                <div class="mt-2">
                    ${permissionList}
                </div>
            </div>
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
        `);
    } catch (e) {
        showMessage('danger', 'Error displaying permissions.');
    }
}

function confirmDeleteRole(roleId, roleName) {
    createTablerPopup('Confirm Deletion', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Delete Role?</h3>
            <p class="text-muted">Are you sure you want to delete role "${roleName}"?<br>This action cannot be undone.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="deleteRole(${roleId})">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function deleteRole(roleId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `dashboard.php?page=roles&action=delete&id=${roleId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo generate_csrf_token(); ?>';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}

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
</script>