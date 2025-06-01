<?php
// dashboard.php - Enhanced main dashboard for new schema
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple session check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load configuration and database
require_once 'config/database.php';
require_once 'includes/dynamic_menu.php';  // ADD THIS LINE

// Get current page from URL parameter
$current_page = isset($_GET['page']) ? sanitize_input($_GET['page']) : 'home';

// Enhanced allowed pages for new schema
$allowed_pages = [
    'home',
    'profile', 
    'students',
    'faculty',
    'courses',
    'reports',
    'settings',
    'users',
    'roles',
    'departments',
    'programs',
    'batches',
    'regulations',
    'academic-years',
    'notifications',
    'my-courses',
    'ward-progress',
    'department',
    'records',
    'gender',
    'religion',
    'menu-management'  // ADD THIS LINE
];

// Validate page parameter
if (!in_array($current_page, $allowed_pages)) {
    $current_page = 'home';
}

// Get enhanced user information with new schema
try {
    $pdo = get_database_connection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.description as role_description,
               r.permissions, r.is_system_role
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? AND u.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
    // Store enhanced user data in session
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['permissions'] = json_decode($user['permissions'], true) ?: [];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    // Get role-specific additional data
    $role_specific_data = get_role_specific_data($user);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("Database error occurred");
}

// Function to get role-specific data
function get_role_specific_data($user) {
    global $pdo;
    $data = [];
    
    try {
        switch ($user['role_name']) {
            case 'student':
                $stmt = $pdo->prepare("
                    SELECT s.*, 
                           b.name as batch_name,
                           p.name as program_name,
                           br.name as branch_name,
                           d.name as department_name,
                           sem.name as current_semester_name
                    FROM students s
                    LEFT JOIN batches b ON s.batch_id = b.id
                    LEFT JOIN programs p ON s.program_id = p.id
                    LEFT JOIN branches br ON s.branch_id = br.id
                    LEFT JOIN departments d ON p.department_id = d.id
                    LEFT JOIN semesters sem ON s.current_semester_id = sem.id
                    WHERE s.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $data['student_info'] = $stmt->fetch();
                break;
                
            case 'faculty':
                $stmt = $pdo->prepare("
                    SELECT f.*, d.name as department_name
                    FROM faculty f
                    LEFT JOIN departments d ON f.department_id = d.id
                    WHERE f.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $data['faculty_info'] = $stmt->fetch();
                break;
                
            case 'hod':
                $stmt = $pdo->prepare("
                    SELECT d.* FROM departments d WHERE d.hod_id = ?
                ");
                $stmt->execute([$user['id']]);
                $data['department_info'] = $stmt->fetch();
                break;
        }
        
        // Get unread notifications count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM system_notifications 
            WHERE user_id = ? AND is_read = 0 AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$user['id']]);
        $notification_data = $stmt->fetch();
        $data['unread_notifications'] = $notification_data['unread_count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error getting role-specific data: " . $e->getMessage());
    }
    
    return $data;
}

// Enhanced page access control
function check_page_access($page, $user_role, $permissions) {
    $page_permissions = [
        'users' => ['all', 'manage_users'],
        'roles' => ['all', 'manage_roles'],
        'students' => ['all', 'manage_students', 'view_students'],
        'faculty' => ['all', 'manage_faculty', 'view_faculty'],
        'departments' => ['all', 'manage_departments'],
        'programs' => ['all', 'manage_programs'],
        'batches' => ['all', 'manage_batches'],
        'regulations' => ['all', 'manage_regulations'],
        'academic-years' => ['all', 'manage_academic_years'],
        'reports' => ['all', 'view_reports', 'generate_reports'],
        'gender' => ['all','view_gender']
    ];
    
    if (!isset($page_permissions[$page])) {
        return true; // Allow access to pages without specific restrictions
    }
    
    $required_permissions = $page_permissions[$page];
    
    // Check if user has any of the required permissions
    return !empty(array_intersect($permissions, $required_permissions));
}

// Check page access
if (!check_page_access($current_page, $user['role_name'], $_SESSION['permissions'])) {
    $current_page = '403'; // Access denied page
}

// Enhanced page titles
$page_titles = [
    'home' => 'Dashboard',
    'profile' => 'My Profile',
    'students' => 'Student Management',
    'faculty' => 'Faculty Management', 
    'courses' => 'Course Management',
    'reports' => 'Reports & Analytics',
    'settings' => 'Account Settings',
    'users' => 'User Management',
    'roles' => 'Role Management',
    'departments' => 'Department Management',
    'programs' => 'Program Management',
    'batches' => 'Batch Management',
    'regulations' => 'Regulation Management',
    'academic-years' => 'Academic Year Management',
    'notifications' => 'Notifications',
    'my-courses' => 'My Courses',
    'ward-progress' => 'Ward Progress',
    'department' => 'Department Overview',
    'records' => 'Records Management',
    'gender'  =>  'Gender Management',
    '403' => 'Access Denied'
];

$page_title = $page_titles[$current_page] ?? 'Dashboard';

// Enhanced breadcrumb generation
function generate_breadcrumb($current_page, $role_name) {
    $breadcrumbs = [
        'home' => [['Dashboard', 'dashboard.php']],
        'students' => [['Dashboard', 'dashboard.php'], ['Student Management', '']],
        'faculty' => [['Dashboard', 'dashboard.php'], ['Faculty Management', '']],
        'departments' => [['Dashboard', 'dashboard.php'], ['Department Management', '']],
        'reports' => [['Dashboard', 'dashboard.php'], ['Reports & Analytics', '']],
    ];
    
    return $breadcrumbs[$current_page] ?? [['Dashboard', 'dashboard.php']];
}

$breadcrumbs = generate_breadcrumb($current_page, $user['role_name']);

// Include header
include 'includes/header.php';

// Include navigation
include 'includes/navmenu.php';

// Include main content
include 'includes/dashboardcontent.php';

// Include footer
include 'includes/footer.php';
?>