<?php
// students_ajax.php - AJAX handlers for student operations

require_once 'config/database.php';
require_once 'includes/auth.php';

// Check permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'head_of_department'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_GET['action'] ?? '';
$student_id = (int)($_GET['id'] ?? 0);

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'toggle_status':
            if (!$student_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
                exit;
            }
            
            $pdo = get_database_connection();
            
            // Get current status
            $stmt = $pdo->prepare("
                SELECT u.status FROM students s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
                exit;
            }
            
            $new_status = $student['status'] === 'active' ? 'inactive' : 'active';
            
            // Update status
            $stmt = $pdo->prepare("
                UPDATE users u 
                JOIN students s ON u.id = s.user_id 
                SET u.status = ?, u.updated_at = NOW() 
                WHERE s.id = ?
            ");
            $result = $stmt->execute([$new_status, $student_id]);
            
            if ($result) {
                log_audit($_SESSION['user_id'], 'toggle_student_status', 'students', $student_id, 
                          ['status' => $student['status']], ['status' => $new_status]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Student status updated successfully.',
                    'new_status' => $new_status
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            }
            break;
            
        case 'get_student_info':
            if (!$student_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
                exit;
            }
            
            $pdo = get_database_connection();
            $stmt = $pdo->prepare("
                SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.status,
                       d.name as department_name, d.code as department_code
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE s.id = ?
            ");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if ($student) {
                echo json_encode(['success' => true, 'student' => $student]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
            }
            break;
            
        case 'check_duplicates':
            $username = sanitize_input($_GET['username'] ?? '');
            $email = sanitize_input($_GET['email'] ?? '');
            $student_id_check = sanitize_input($_GET['student_id'] ?? '');
            $exclude_user_id = (int)($_GET['exclude_user_id'] ?? 0);
            
            $pdo = get_database_connection();
            $duplicates = [];
            
            // Check username
            if ($username) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $exclude_user_id]);
                if ($stmt->fetch()) {
                    $duplicates[] = 'username';
                }
            }
            
            // Check email
            if ($email) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $exclude_user_id]);
                if ($stmt->fetch()) {
                    $duplicates[] = 'email';
                }
            }
            
            // Check student ID
            if ($student_id_check) {
                $stmt = $pdo->prepare("
                    SELECT s.id FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE s.student_id = ? AND u.id != ?
                ");
                $stmt->execute([$student_id_check, $exclude_user_id]);
                if ($stmt->fetch()) {
                    $duplicates[] = 'student_id';
                }
            }
            
            echo json_encode([
                'success' => true,
                'duplicates' => $duplicates,
                'has_duplicates' => !empty($duplicates)
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Students AJAX error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred.']);
}
?>
