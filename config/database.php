<?php
// config/database.php - Enhanced for new database schema

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'cerp_2');
define('DB_USER', 'admin');
define('DB_PASS', 'Nihita@1981');
define('DB_CHARSET', 'utf8mb4');

// Enhanced Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('EMAIL_VERIFICATION_REQUIRED', false); // New feature toggle
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour for password reset tokens

// Application settings - Enhanced
define('SITE_NAME', 'Enhanced Educational Management System');
define('SITE_URL', 'http://localhost/cerp');
define('UPLOAD_PATH', 'uploads/');
define('PROFILE_PHOTO_PATH', 'uploads/profiles/');
define('DOCUMENT_PATH', 'uploads/documents/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Database connection function - Enhanced
function get_database_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// Enhanced UUID generation for new schema
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Security functions (unchanged)
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Enhanced audit logging for new schema
function log_audit($user_id, $action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    $pdo = get_database_connection();
    $stmt = $pdo->prepare("
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, 
                              ip_address, user_agent, session_id, request_method, request_url, severity) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $action,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        session_id(),
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '',
        'INFO'
    ]);
}

// Enhanced session management for new schema
function start_secure_session() {
    if (session_status() == PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// File upload helper functions for new schema
function upload_file($file, $destination_folder, $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded or invalid file.'];
    }
    
    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit.'];
    }
    
    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }
    
    // Create destination folder if it doesn't exist
    if (!file_exists($destination_folder)) {
        mkdir($destination_folder, 0755, true);
    }
    
    // Generate unique filename
    $unique_filename = generate_uuid() . '.' . $file_extension;
    $destination_path = $destination_folder . '/' . $unique_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination_path)) {
        return ['success' => true, 'filename' => $unique_filename, 'path' => $destination_path];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Helper function to get lookup data for new schema
function get_lookup_data($table, $order_by = 'name') {
    $pdo = get_database_connection();
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$table} ORDER BY {$order_by}");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching lookup data from {$table}: " . $e->getMessage());
        return [];
    }
}

// Get current academic year
function get_current_academic_year() {
    $pdo = get_database_connection();
    try {
        $stmt = $pdo->prepare("SELECT * FROM academic_years WHERE is_current = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error fetching current academic year: " . $e->getMessage());
        return null;
    }
}

// Notification helper for new schema
function create_notification($user_id, $title, $message, $type = 'info', $action_url = null) {
    $pdo = get_database_connection();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (user_id, title, message, notification_type, action_url) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $title, $message, $type, $action_url]);
        return true;
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

start_secure_session();
?>