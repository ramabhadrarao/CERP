<?php
// includes/auth.php - Authentication functions

require_once 'config/database.php';

// User authentication
function authenticate_user($username, $password) {
    $pdo = get_database_connection();
    
    // Check for too many failed attempts
    if (is_account_locked($username)) {
        return ['success' => false, 'message' => 'Account temporarily locked due to too many failed attempts.'];
    }
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.permissions 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'
    ");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Clear failed attempts
        clear_failed_attempts($username);
        
        // Create session
        $session_token = create_user_session($user['id']);
        
        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true);
        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['session_token'] = $session_token;
        $_SESSION['login_time'] = time();
        
        log_audit($user['id'], 'login');
        
        return ['success' => true, 'user' => $user];
    } else {
        // Record failed attempt
        record_failed_attempt($username);
        log_audit(null, 'failed_login', null, null, null, ['username' => $username]);
        
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
}

function create_user_session($user_id) {
    $pdo = get_database_connection();
    $session_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    // Clean old sessions
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? OR expires_at < NOW()");
    $stmt->execute([$user_id]);
    
    // Create new session
    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $session_token, $expires_at]);
    
    return $session_token;
}

function validate_session() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    $pdo = get_database_connection();
    $stmt = $pdo->prepare("
        SELECT s.*, u.status 
        FROM user_sessions s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.session_token = ? AND s.expires_at > NOW() AND u.status = 'active'
    ");
    $stmt->execute([$_SESSION['session_token']]);
    $session = $stmt->fetch();
    
    if (!$session || $session['user_id'] != $_SESSION['user_id']) {
        logout_user();
        return false;
    }
    
    // Extend session
    $new_expires = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    $stmt = $pdo->prepare("UPDATE user_sessions SET expires_at = ? WHERE session_token = ?");
    $stmt->execute([$new_expires, $_SESSION['session_token']]);
    
    return true;
}

function logout_user() {
    if (isset($_SESSION['user_id'])) {
        log_audit($_SESSION['user_id'], 'logout');
        
        // Remove session from database
        if (isset($_SESSION['session_token'])) {
            $pdo = get_database_connection();
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_SESSION['session_token']]);
        }
    }
    
    // Clear session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function require_login() {
    if (!validate_session()) {
        header('Location: login.php');
        exit;
    }
}

function require_permission($permission) {
    require_login();
    
    if (!has_permission($permission)) {
        header('HTTP/1.0 403 Forbidden');
        include 'includes/403.php';
        exit;
    }
}

function has_permission($permission) {
    if (!isset($_SESSION['permissions'])) {
        return false;
    }
    
    $permissions = $_SESSION['permissions'];
    
    // Super admin has all permissions
    if (in_array('all', $permissions)) {
        return true;
    }
    
    return in_array($permission, $permissions);
}

function get_user_role() {
    return $_SESSION['role'] ?? 'guest';
}

// Failed login attempt tracking
function record_failed_attempt($username) {
    $key = 'failed_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
    $attempts = $_SESSION[$key] ?? 0;
    $_SESSION[$key] = $attempts + 1;
    $_SESSION[$key . '_time'] = time();
}

function is_account_locked($username) {
    $key = 'failed_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
    $attempts = $_SESSION[$key] ?? 0;
    $last_attempt = $_SESSION[$key . '_time'] ?? 0;
    
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        if (time() - $last_attempt < LOCKOUT_TIME) {
            return true;
        } else {
            // Reset attempts after lockout period
            unset($_SESSION[$key]);
            unset($_SESSION[$key . '_time']);
        }
    }
    
    return false;
}

function clear_failed_attempts($username) {
    $key = 'failed_attempts_' . md5($username . $_SERVER['REMOTE_ADDR']);
    unset($_SESSION[$key]);
    unset($_SESSION[$key . '_time']);
}

// Password utilities
function generate_secure_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
}

function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    return $errors;
}
?>