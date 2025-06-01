<?php
// pages/users/ajax.php - AJAX Handler for User Operations

// Prevent any output buffering issues
if (ob_get_level()) {
    ob_end_clean();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in and has proper permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Include required files
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Get action and parameters from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Verify CSRF token
$token = $_GET['token'] ?? $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

// Rate limiting - simple implementation
$rate_limit_key = 'ajax_rate_limit_' . $_SESSION['user_id'];
$current_time = time();
$rate_limit_window = 60; // 1 minute
$max_requests = 30; // 30 requests per minute

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'start_time' => $current_time];
}

$rate_data = $_SESSION[$rate_limit_key];
if ($current_time - $rate_data['start_time'] > $rate_limit_window) {
    // Reset rate limit window
    $_SESSION[$rate_limit_key] = ['count' => 1, 'start_time' => $current_time];
} else {
    // Check if limit exceeded
    if ($rate_data['count'] >= $max_requests) {
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please try again later.']);
        exit;
    }
    $_SESSION[$rate_limit_key]['count']++;
}

try {
    switch ($action) {
        case 'toggle_status':
            $result = toggle_user_status($user_id);
            echo json_encode($result);
            break;
            
        case 'reset_password':
            $result = reset_user_password($user_id);
            echo json_encode($result);
            break;
            
        case 'delete':
            $result = delete_user($user_id);
            echo json_encode($result);
            break;
            
        case 'get_user_details':
            $result = get_user_details($user_id);
            echo json_encode($result);
            break;
            
        case 'validate_username':
            $username = $_POST['username'] ?? '';
            $exclude_id = (int)($_POST['exclude_id'] ?? 0);
            $result = validate_username($username, $exclude_id);
            echo json_encode($result);
            break;
            
        case 'validate_email':
            $email = $_POST['email'] ?? '';
            $exclude_id = (int)($_POST['exclude_id'] ?? 0);
            $result = validate_email($email, $exclude_id);
            echo json_encode($result);
            break;
            
        case 'get_user_sessions':
            $result = get_user_sessions($user_id);
            echo json_encode($result);
            break;
            
        case 'terminate_sessions':
            $result = terminate_user_sessions($user_id);
            echo json_encode($result);
            break;
            
        case 'bulk_action':
            $user_ids = $_POST['user_ids'] ?? [];
            $bulk_action = $_POST['bulk_action'] ?? '';
            $result = handle_bulk_action($user_ids, $bulk_action);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} catch (Exception $e) {
    error_log("AJAX error in action '{$action}': " . $e->getMessage() . " - File: " . $e->getFile() . " - Line: " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}

function toggle_user_status($id) {
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

function delete_user($id) {
    $pdo = get_database_connection();
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Prevent deleting self
    if ($id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'You cannot delete your own account.'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get user info for audit
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Check if user has dependent records
        $dependent_checks = [
            ['table' => 'students', 'column' => 'user_id', 'name' => 'student records'],
            ['table' => 'faculty', 'column' => 'user_id', 'name' => 'faculty records'],
            ['table' => 'audit_log', 'column' => 'user_id', 'name' => 'audit log entries']
        ];
        
        foreach ($dependent_checks as $check) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$check['table']} WHERE {$check['column']} = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Cannot delete user. They have {$result['count']} associated {$check['name']}."];
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
            
            $pdo->commit();
            
            return [
                'success' => true, 
                'message' => "User {$user['first_name']} {$user['last_name']} deleted successfully."
            ];
        } else {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to delete user.'];
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Cannot delete user. Database error occurred.'];
    }
}

function get_user_details($id) {
    $pdo = get_database_connection();
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, r.description as role_description,
                   COUNT(us.id) as active_sessions,
                   MAX(us.last_activity) as last_session_activity
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN user_sessions us ON u.id = us.user_id AND us.expires_at > NOW()
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Remove sensitive data
        unset($user['password_hash']);
        unset($user['password_reset_token']);
        
        // Get additional user statistics
        $stats = [];
        
        // Check if user is a student
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE user_id = ?");
        $stmt->execute([$id]);
        $stats['is_student'] = $stmt->fetch()['count'] > 0;
        
        // Check if user is faculty
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM faculty WHERE user_id = ?");
        $stmt->execute([$id]);
        $stats['is_faculty'] = $stmt->fetch()['count'] > 0;
        
        // Get login count (from audit log)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM audit_log WHERE user_id = ? AND action = 'login'");
        $stmt->execute([$id]);
        $stats['total_logins'] = $stmt->fetch()['count'];
        
        return [
            'success' => true,
            'user' => $user,
            'stats' => $stats
        ];
        
    } catch (Exception $e) {
        error_log("Get user details error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// New function: Validate username uniqueness
function validate_username($username, $exclude_id = 0) {
    if (empty($username)) {
        return ['success' => false, 'message' => 'Username is required.'];
    }
    
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters long.'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        return ['success' => false, 'message' => 'Username can only contain letters, numbers, dots, underscores, and hyphens.'];
    }
    
    $pdo = get_database_connection();
    
    try {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        return ['success' => true, 'message' => 'Username is available.'];
        
    } catch (Exception $e) {
        error_log("Validate username error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// New function: Validate email uniqueness
function validate_email($email, $exclude_id = 0) {
    if (empty($email)) {
        return ['success' => false, 'message' => 'Email is required.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format.'];
    }
    
    $pdo = get_database_connection();
    
    try {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];
        
        if ($exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        return ['success' => true, 'message' => 'Email is available.'];
        
    } catch (Exception $e) {
        error_log("Validate email error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// New function: Get user sessions
function get_user_sessions($id) {
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    $pdo = get_database_connection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                session_token,
                device_info,
                ip_address,
                user_agent,
                created_at,
                last_activity,
                expires_at,
                CASE 
                    WHEN expires_at > NOW() THEN 'active'
                    ELSE 'expired'
                END as status
            FROM user_sessions 
            WHERE user_id = ? 
            ORDER BY last_activity DESC
        ");
        $stmt->execute([$id]);
        $sessions = $stmt->fetchAll();
        
        return [
            'success' => true,
            'sessions' => $sessions,
            'total_sessions' => count($sessions),
            'active_sessions' => count(array_filter($sessions, fn($s) => $s['status'] === 'active'))
        ];
        
    } catch (Exception $e) {
        error_log("Get user sessions error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// New function: Terminate user sessions
function terminate_user_sessions($id) {
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid user ID.'];
    }
    
    // Prevent terminating own sessions
    if ($id == $_SESSION['user_id']) {
        return ['success' => false, 'message' => 'Cannot terminate your own sessions.'];
    }
    
    $pdo = get_database_connection();
    
    try {
        // Get user info
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Count active sessions before deletion
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_sessions WHERE user_id = ? AND expires_at > NOW()");
        $stmt->execute([$id]);
        $active_count = $stmt->fetch()['count'];
        
        // Delete all sessions for the user
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'terminate_sessions', 'user_sessions', $id, null, ['terminated_sessions' => $active_count]);
            
            return [
                'success' => true,
                'message' => "Terminated {$active_count} active sessions for {$user['first_name']} {$user['last_name']}.",
                'terminated_count' => $active_count
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to terminate sessions.'];
        
    } catch (Exception $e) {
        error_log("Terminate sessions error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// New function: Handle bulk actions
function handle_bulk_action($user_ids, $bulk_action) {
    if (empty($user_ids) || !is_array($user_ids)) {
        return ['success' => false, 'message' => 'No users selected.'];
    }
    
    if (empty($bulk_action)) {
        return ['success' => false, 'message' => 'No action specified.'];
    }
    
    // Limit bulk operations to prevent abuse
    if (count($user_ids) > 100) {
        return ['success' => false, 'message' => 'Too many users selected. Maximum 100 users allowed.'];
    }
    
    // Remove current user from bulk operations
    $user_ids = array_filter($user_ids, fn($id) => (int)$id !== $_SESSION['user_id']);
    $user_ids = array_map('intval', $user_ids);
    
    if (empty($user_ids)) {
        return ['success' => false, 'message' => 'No valid users selected.'];
    }
    
    $pdo = get_database_connection();
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    try {
        $pdo->beginTransaction();
        
        switch ($bulk_action) {
            case 'activate':
                foreach ($user_ids as $id) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ? AND status != 'active'");
                    if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
                        $success_count++;
                        log_audit($_SESSION['user_id'], 'bulk_activate', 'users', $id, null, ['status' => 'active']);
                    }
                }
                break;
                
            case 'deactivate':
                foreach ($user_ids as $id) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ? AND status != 'inactive'");
                    if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
                        $success_count++;
                        // Terminate sessions for deactivated users
                        $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$id]);
                        log_audit($_SESSION['user_id'], 'bulk_deactivate', 'users', $id, null, ['status' => 'inactive']);
                    }
                }
                break;
                
            case 'delete':
                foreach ($user_ids as $id) {
                    // Check for dependent records
                    $dependent_checks = [
                        ['table' => 'students', 'column' => 'user_id'],
                        ['table' => 'faculty', 'column' => 'user_id']
                    ];
                    
                    $can_delete = true;
                    foreach ($dependent_checks as $check) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$check['table']} WHERE {$check['column']} = ?");
                        $stmt->execute([$id]);
                        if ($stmt->fetch()['count'] > 0) {
                            $can_delete = false;
                            $error_count++;
                            $errors[] = "User ID {$id} has dependent records";
                            break;
                        }
                    }
                    
                    if ($can_delete) {
                        // Delete sessions first
                        $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?")->execute([$id]);
                        // Delete user
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
                            $success_count++;
                            log_audit($_SESSION['user_id'], 'bulk_delete', 'users', $id, null, null);
                        }
                    }
                }
                break;
                
            default:
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Invalid bulk action.'];
        }
        
        $pdo->commit();
        
        $message = "Bulk action completed. {$success_count} users processed successfully.";
        if ($error_count > 0) {
            $message .= " {$error_count} users had errors.";
        }
        
        return [
            'success' => true,
            'message' => $message,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk action error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred during bulk action.'];
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
?> 