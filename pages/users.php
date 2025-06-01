<?php
// pages/users.php - Main User Management Controller

// Check if user has admin permission
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access user management. Only super administrators can manage users.</p>
          </div>';
    return;
}

// Handle AJAX requests FIRST before any output
if (isset($_GET['ajax']) && isset($_GET['id'])) {
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    
    $ajax_user_id = (int)$_GET['id'];
    $ajax_action = sanitize_input($_GET['ajax']);
    
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }
    
    try {
        switch ($ajax_action) {
            case 'toggle_status':
                $result = ajax_toggle_user_status($ajax_user_id);
                echo json_encode($result);
                exit;
                
            case 'reset_password':
                $result = ajax_reset_user_password($ajax_user_id);
                echo json_encode($result);
                exit;
                
            case 'delete':
                $result = ajax_delete_user($ajax_user_id);
                echo json_encode($result);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action.']);
                exit;
        }
    } catch (Exception $e) {
        error_log("AJAX error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error occurred.']);
        exit;
    }
}

// AJAX Functions
function ajax_toggle_user_status($id) {
    $pdo = get_database_connection();
    
    if (!$id || $id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot modify this user.'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT status, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $id]);
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'toggle_status', 'users', $id, 
                ['status' => $user['status']], ['status' => $new_status]);
            
            // Terminate user sessions if deactivating
            if ($new_status === 'inactive') {
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->execute([$id]);
            }
            
            return [
                'success' => true, 
                'message' => "User {$user['first_name']} {$user['last_name']} status changed to {$new_status}.",
                'new_status' => $new_status
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update status.'];
        
    } catch (Exception $e) {
        error_log("Toggle status error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function ajax_reset_user_password($id) {
    $pdo = get_database_connection();
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    try {
        // Get user info
        $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Generate secure password
        $new_password = generate_secure_password(12);
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$password_hash, $id]);
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'reset_password', 'users', $id, null, null);
            
            // Terminate all user sessions to force re-login
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$id]);
            
            return [
                'success' => true, 
                'message' => "Password reset successfully for {$user['first_name']} {$user['last_name']}.",
                'new_password' => $new_password,
                'user_name' => $user['first_name'] . ' ' . $user['last_name']
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to reset password.'];
        
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function ajax_delete_user($id) {
    $pdo = get_database_connection();
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'You cannot delete your own account.'];
    }
    
    try {
        // Get user info for audit
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
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
        
        // Delete user sessions first
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$id]);
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            log_audit($_SESSION['user_id'], 'delete_user', 'users', $id, $user, null);
            
            return [
                'success' => true, 
                'message' => "User {$user['first_name']} {$user['last_name']} deleted successfully."
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Cannot delete user. They may have associated records.'];
    }
}

// Helper function to generate secure password
function generate_secure_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    // Ensure at least one character from each type
    $types = [
        'abcdefghijklmnopqrstuvwxyz', // lowercase
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ', // uppercase
        '0123456789',                 // numbers
        '!@#$%^&*'                   // special
    ];
    
    // Add one character from each type
    foreach ($types as $type) {
        $password .= $type[random_int(0, strlen($type) - 1)];
    }
    
    // Fill the rest with random characters
    for ($i = 4; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    // Shuffle the password
    return str_shuffle($password);
}

// Get action parameter
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';

// Set page title based on action
$page_titles = [
    'list' => 'User Management',
    'add' => 'Add New User',
    'edit' => 'Edit User',
    'bulk_import' => 'Bulk Import Users',
    'view' => 'User Details',
    'export' => 'Export Users'
];

$page_title = $page_titles[$action] ?? 'User Management';

// Route to appropriate page based on action
switch ($action) {
    case 'add':
        include 'users/add.php';
        break;
        
    case 'edit':
        include 'users/edit.php';
        break;
        
    case 'bulk_import':
        include 'users/bulk_import.php';
        break;
        
    case 'view':
        include 'users/view.php';
        break;
        
    case 'export':
        include 'users/export.php';
        break;
        
    case 'list':
    default:
        include 'users/index.php';
        break;
}
?>