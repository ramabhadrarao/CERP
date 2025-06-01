<?php
// export_students.php - Export students data

require_once 'config/database.php';
require_once 'includes/auth.php';

// Check permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'head_of_department', 'faculty'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$format = isset($_GET['format']) ? sanitize_input($_GET['format']) : 'csv';

// Get all students
try {
    $pdo = get_database_connection();
    $stmt = $pdo->query("
        SELECT s.student_id, u.first_name, u.last_name, u.email, u.phone, u.address,
               u.status, s.semester, s.year_of_admission, d.name as department_name, d.code as department_code,
               u.created_at
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN departments d ON s.department_id = d.id
        ORDER BY u.first_name, u.last_name
    ");
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Export students error: " . $e->getMessage());
    header('Location: dashboard.php?page=students&error=' . urlencode('Failed to export students data.'));
    exit;
}

if ($format === 'excel') {
    // For Excel export (simplified - you might want to use PHPSpreadsheet for better Excel support)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.xls"');
} else {
    // CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');
}

header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add BOM for proper Excel UTF-8 support
if ($format === 'csv') {
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
}

// Header row
$headers = [
    'Student ID',
    'First Name',
    'Last Name',
    'Email',
    'Phone',
    'Department',
    'Department Code',
    'Semester',
    'Year of Admission',
    'Status',
    'Address',
    'Registration Date'
];

fputcsv($output, $headers);

// Data rows
foreach ($students as $student) {
    $row = [
        $student['student_id'],
        $student['first_name'],
        $student['last_name'],
        $student['email'],
        $student['phone'] ?: '',
        $student['department_name'] ?: '',
        $student['department_code'] ?: '',
        $student['semester'] ?: '',
        $student['year_of_admission'],
        ucfirst($student['status']),
        $student['address'] ?: '',
        date('Y-m-d', strtotime($student['created_at']))
    ];
    
    fputcsv($output, $row);
}

fclose($output);

// Log the export action
log_audit($_SESSION['user_id'], 'export_students', 'students', null, null, [
    'format' => $format,
    'count' => count($students)
]);

exit;
?>

<?php
// create_departments.php - Sample script to create departments (run once)

require_once 'config/database.php';

try {
    $pdo = get_database_connection();
    
    // Sample departments
    $departments = [
        ['Computer Science', 'CS'],
        ['Electrical Engineering', 'EE'],
        ['Mechanical Engineering', 'ME'],
        ['Civil Engineering', 'CE'],
        ['Electronics & Communication', 'ECE'],
        ['Information Technology', 'IT'],
        ['Chemical Engineering', 'CH'],
        ['Aerospace Engineering', 'AE'],
        ['Biotechnology', 'BT'],
        ['Mathematics', 'MATH'],
        ['Physics', 'PHY'],
        ['Chemistry', 'CHEM'],
        ['Business Administration', 'MBA'],
        ['Commerce', 'COM'],
        ['Arts', 'ARTS']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name, code) VALUES (?, ?)");
    
    foreach ($departments as $dept) {
        $stmt->execute($dept);
    }
    
    echo "Departments created successfully!";
    
} catch (Exception $e) {
    echo "Error creating departments: " . $e->getMessage();
}
?>