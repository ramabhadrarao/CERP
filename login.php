<?php
// login.php - Enhanced login handler for new schema
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (validate_session()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $result = authenticate_user($username, $password);
                        
            if ($result['success']) {
                // Enhanced login success handling
                $user = $result['user'];
                
                // Update last login timestamp in new schema
                try {
                    $pdo = get_database_connection();
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch (Exception $e) {
                    error_log("Failed to update last login: " . $e->getMessage());
                }
                
                // Create notification for successful login (if feature enabled)
                if (defined('ENABLE_LOGIN_NOTIFICATIONS') && ENABLE_LOGIN_NOTIFICATIONS) {
                    create_notification(
                        $user['id'], 
                        'Login Successful', 
                        'You have successfully logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown IP'),
                        'success'
                    );
                }
                
                // Check if user needs to change password (new feature)
                if (isset($user['password_reset_required']) && $user['password_reset_required']) {
                    $_SESSION['password_change_required'] = true;
                    header('Location: change-password.php?required=1');
                    exit;
                }
                
                // Check email verification status (new feature)
                if (EMAIL_VERIFICATION_REQUIRED && !$user['email_verified']) {
                    $_SESSION['email_verification_required'] = true;
                    header('Location: verify-email.php');
                    exit;
                }
                
                // Redirect based on user role
                $redirect_url = get_role_based_redirect($user['role_name']);
                header('Location: ' . $redirect_url);
                exit;
            } else {
                $error = $result['message'];
                
                // Enhanced failed login handling
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                error_log("Failed login attempt for username: {$username} from IP: {$ip_address}");
            }
        }
    }
}

// Check for logout message
if (isset($_GET['logout'])) {
    $success = 'You have been successfully logged out.';
}

// Check for password reset success
if (isset($_GET['password_reset'])) {
    $success = 'Password reset successfully. Please login with your new password.';
}

// Check for email verification success
if (isset($_GET['email_verified'])) {
    $success = 'Email verified successfully. You can now login.';
}

// Helper function to get role-based redirect URL
function get_role_based_redirect($role_name) {
    $role_redirects = [
        'super_admin' => 'dashboard.php',
        'admin' => 'dashboard.php',
        'principal' => 'dashboard.php?page=reports',
        'hod' => 'dashboard.php?page=department',
        'faculty' => 'dashboard.php?page=courses',
        'student' => 'dashboard.php?page=my-courses',
        'parent' => 'dashboard.php?page=ward-progress',
        'staff' => 'dashboard.php?page=records',
        'guest' => 'dashboard.php'
    ];
    
    return $role_redirects[$role_name] ?? 'dashboard.php';
}

// Enhanced login attempts tracking (for new schema)
function track_login_attempt($username, $success = false, $ip_address = null) {
    $pdo = get_database_connection();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (username, ip_address, success, attempted_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $username, 
            $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown', 
            $success ? 1 : 0
        ]);
    } catch (Exception $e) {
        error_log("Failed to track login attempt: " . $e->getMessage());
    }
}

// Include the login form
include 'login.html';
?>