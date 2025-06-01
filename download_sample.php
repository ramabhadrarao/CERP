<?php
// download_sample.php - Download CSV template for bulk import

require_once 'config/database.php';
require_once 'includes/auth.php';

// Check permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'head_of_department'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_import_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Sample data with proper headers
$sample_data = [
    // Header row
    ['first_name', 'last_name', 'email', 'student_id', 'username', 'phone', 'address', 'department_code', 'semester', 'year_of_admission'],
    
    // Sample data rows
    ['John', 'Doe', 'john.doe@example.com', 'STU2024001', 'john.doe', '+91-9876543210', '123 Main Street, City', 'CS', '1', '2024'],
    ['Jane', 'Smith', 'jane.smith@example.com', 'STU2024002', 'jane.smith', '+91-9876543211', '456 Oak Avenue, City', 'EE', '3', '2024'],
    ['Bob', 'Johnson', 'bob.johnson@example.com', 'STU2024003', '', '+91-9876543212', '', 'ME', '5', '2023'],
    ['Alice', 'Wilson', 'alice.wilson@example.com', 'STU2024004', 'alice.wilson', '', '789 Pine Road, City', 'CE', '2', '2024'],
    ['Mike', 'Brown', 'mike.brown@example.com', 'STU2024005', 'mike.brown', '+91-9876543213', '321 Elm Street, City', 'CS', '1', '2024']
];

// Output CSV
$output = fopen('php://output', 'w');

// Add BOM for proper Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

foreach ($sample_data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>