<?php
// dashboard.php - Main dashboard with modular structure
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

// Get current page from URL parameter
$current_page = isset($_GET['page']) ? sanitize_input($_GET['page']) : 'home';

// Define allowed pages for security
$allowed_pages = [
    'home',
    'profile', 
    'students',
    'faculty',
    'courses',
    'reports',
    'settings',
    'users'
];

// Validate page parameter
if (!in_array($current_page, $allowed_pages)) {
    $current_page = 'home';
}

// Get user information
try {
    $pdo = get_database_connection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("Database error occurred");
}

// Set page title based on current page
$page_titles = [
    'home' => 'Dashboard',
    'profile' => 'My Profile',
    'students' => 'Students Management',
    'faculty' => 'Faculty Management', 
    'courses' => 'Courses Management',
    'reports' => 'Reports',
    'settings' => 'Settings',
    'users' => 'User Management'
];

$page_title = $page_titles[$current_page] ?? 'Dashboard';

// Include header
include 'includes/header.php';

// Include navigation
include 'includes/navmenu.php';

// Include main content
include 'includes/dashboardcontent.php';

// Include footer
include 'includes/footer.php';
?>