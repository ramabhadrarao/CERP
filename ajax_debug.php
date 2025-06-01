<?php
// ajax_debug.php - Debug AJAX calls for user management with popup test
require_once 'config/database.php';
require_once 'includes/auth.php';

// Start session and check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = (int)($_GET['id'] ?? 0);

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'test_popup':
            // Test popup functionality with proper z-index
            echo json_encode([
                'success' => true, 
                'message' => 'Popup test completed successfully!',
                'popup_html' => '<div class="alert alert-success">
                    <h4>✅ Z-Index Test Successful</h4>
                    <p>This popup appeared on top of all elements including sidebar and header.</p>
                    <p><strong>Current Z-index hierarchy:</strong></p>
                    <ul>
                        <li>Page content: 1</li>
                        <li>Top header: 1020</li>
                        <li>Sidebar: 1030</li>
                        <li>Dropdown menus: 1050</li>
                        <li>Modal backdrop: 1055</li>
                        <li>Modals: 1060</li>
                        <li>Tooltips: 1070</li>
                        <li>Custom popups: 9999 ✅</li>
                    </ul>
                </div>'
            ]);
            break;
            
        case 'toggle_status':
            if (!$user_id || $user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot modify this user.']);
                exit;
            }
            
            $pdo = get_database_connection();
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
                exit;
            }
            
            $new_status = $user['status'] === 'active' ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$new_status, $user_id]);
            
            if ($result) {
                // Log the action
                log_audit($_SESSION['user_id'], 'toggle_status', 'users', $user_id, ['status' => $user['status']], ['status' => $new_status]);
                echo json_encode(['success' => true, 'message' => 'User status updated.', 'new_status' => $new_status]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            }
            break;
            
        case 'reset_password':
            if (!$user_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            
            // Generate secure password
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            $new_password = substr(str_shuffle(str_repeat($chars, ceil(12/strlen($chars)))), 0, 12);
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $pdo = get_database_connection();
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$password_hash, $user_id]);
            
            if ($result) {
                // Log the action
                log_audit($_SESSION['user_id'], 'reset_password', 'users', $user_id, null, null);
                echo json_encode(['success' => true, 'message' => 'Password reset successfully.', 'new_password' => $new_password]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
    
} catch (Exception $e) {
    error_log("AJAX error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>