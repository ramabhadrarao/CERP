<?php
// pages/roles.php - Complete Enhanced Role Management System

// Check if user has admin permission
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access role management. Only super administrators can manage roles.</p>
          </div>';
    return;
}

// Get action and parameters
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$role_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get success/error messages from URL parameters
if (isset($_GET['success'])) {
    $message = sanitize_input($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = sanitize_input($_GET['error']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_role($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_role($role_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_role($role_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=roles&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=roles&error=' . urlencode($result['message']));
                    exit;
                }
                break;
        }
    }
}

// Comprehensive available permissions for the educational management system
$available_permissions = [
    // System Administration
    'all' => 'Full System Access (Super Admin)',
    'manage_users' => 'Manage Users',
    'manage_roles' => 'Manage Roles & Permissions',
    'system_settings' => 'System Settings',
    'view_audit_logs' => 'View Audit Logs',
    'manage_system_backups' => 'Manage System Backups',
    
    // Institutional Management
    'manage_college' => 'Manage College Information',
    'manage_departments' => 'Manage Departments',
    'manage_programs' => 'Manage Programs',
    'manage_branches' => 'Manage Branches',
    'manage_regulations' => 'Manage Regulations',
    'manage_academic_years' => 'Manage Academic Years',
    'manage_batches' => 'Manage Batches',
    'manage_semesters' => 'Manage Semesters',
    
    // Student Management
    'manage_students' => 'Manage Students',
    'view_students' => 'View Students',
    'manage_student_registrations' => 'Manage Student Course Registrations',
    'view_student_registrations' => 'View Student Course Registrations',
    'manage_student_documents' => 'Manage Student Documents',
    'view_student_documents' => 'View Student Documents',
    'approve_student_registrations' => 'Approve Student Registrations',
    'manage_student_admissions' => 'Manage Student Admissions',
    'view_student_records' => 'View Student Academic Records',
    
    // Faculty Management
    'manage_faculty' => 'Manage Faculty',
    'view_faculty' => 'View Faculty',
    'manage_faculty_assignments' => 'Manage Faculty Course Assignments',
    'view_faculty_assignments' => 'View Faculty Course Assignments',
    'manage_faculty_qualifications' => 'Manage Faculty Qualifications',
    'manage_faculty_workload' => 'Manage Faculty Workload',
    'approve_faculty_applications' => 'Approve Faculty Applications',
    
    // Course Management
    'manage_courses' => 'Manage Courses',
    'view_courses' => 'View Courses',
    'manage_course_prerequisites' => 'Manage Course Prerequisites',
    'manage_electives' => 'Manage Elective Groups',
    'view_electives' => 'View Elective Groups',
    'approve_course_proposals' => 'Approve Course Proposals',
    'manage_course_syllabus' => 'Manage Course Syllabus',
    'manage_course_schedule' => 'Manage Course Schedule',
    
    // Assessment & Marks
    'manage_assessments' => 'Manage Assessment Components',
    'grade_students' => 'Grade Students',
    'view_grades' => 'View Grades',
    'lock_unlock_marks' => 'Lock/Unlock Marks',
    'verify_marks' => 'Verify Marks',
    'modify_marks' => 'Modify Student Marks',
    'approve_grade_changes' => 'Approve Grade Changes',
    'generate_transcripts' => 'Generate Academic Transcripts',
    
    // Attendance Management
    'manage_class_schedule' => 'Manage Class Schedule',
    'mark_attendance' => 'Mark Attendance',
    'view_attendance' => 'View Attendance',
    'generate_attendance_reports' => 'Generate Attendance Reports',
    'modify_attendance' => 'Modify Attendance Records',
    'approve_attendance_changes' => 'Approve Attendance Changes',
    
    // Research & Publications (Faculty)
    'manage_publications' => 'Manage Research Publications',
    'view_publications' => 'View Research Publications',
    'approve_publications' => 'Approve Publication Records',
    'manage_research_projects' => 'Manage Research Projects',
    
    // Reporting & Analytics
    'view_reports' => 'View Reports',
    'generate_reports' => 'Generate Reports',
    'view_analytics' => 'View Analytics Dashboard',
    'export_data' => 'Export System Data',
    'generate_certificates' => 'Generate Certificates',
    'view_institutional_reports' => 'View Institutional Reports',
    'access_financial_reports' => 'Access Financial Reports',
    
    // Notifications & Communications
    'manage_notifications' => 'Manage System Notifications',
    'send_announcements' => 'Send Announcements',
    'view_announcements' => 'View Announcements',
    'send_bulk_communications' => 'Send Bulk Communications',
    'manage_communication_templates' => 'Manage Communication Templates',
    
    // Profile & Personal Access
    'view_profile' => 'View Own Profile',
    'edit_profile' => 'Edit Own Profile',
    'change_password' => 'Change Own Password',
    'upload_documents' => 'Upload Personal Documents',
    
    // Parent-specific permissions
    'view_ward_progress' => 'View Ward Progress (Parents)',
    'view_ward_attendance' => 'View Ward Attendance (Parents)',
    'view_ward_grades' => 'View Ward Grades (Parents)',
    'communicate_with_faculty' => 'Communicate with Faculty (Parents)',
    
    // Student-specific permissions
    'course_registration' => 'Course Registration (Students)',
    'select_electives' => 'Select Electives (Students)',
    'view_own_grades' => 'View Own Grades (Students)',
    'view_own_attendance' => 'View Own Attendance (Students)',
    'download_certificates' => 'Download Certificates (Students)',
    'view_fee_details' => 'View Fee Details (Students)',
    'apply_for_documents' => 'Apply for Documents (Students)',
    
    // Staff-specific permissions
    'data_entry' => 'Data Entry Operations',
    'manage_records' => 'Manage Administrative Records',
    'process_applications' => 'Process Applications',
    'verify_documents' => 'Verify Documents',
    'generate_id_cards' => 'Generate ID Cards',
    
    // Examination Management
    'manage_exam_schedule' => 'Manage Examination Schedule',
    'conduct_examinations' => 'Conduct Examinations',
    'manage_exam_results' => 'Manage Examination Results',
    'publish_results' => 'Publish Examination Results',
    
    // Library Management (if applicable)
    'manage_library' => 'Manage Library Resources',
    'issue_books' => 'Issue Books',
    'manage_library_fines' => 'Manage Library Fines',
    
    // Finance & Fee Management
    'manage_fees' => 'Manage Fee Structure',
    'collect_fees' => 'Collect Fees',
    'generate_fee_receipts' => 'Generate Fee Receipts',
    'view_fee_reports' => 'View Fee Reports',
    
    // Placement & Career Services
    'manage_placements' => 'Manage Placement Activities',
    'coordinate_campus_recruitment' => 'Coordinate Campus Recruitment',
    'manage_company_relations' => 'Manage Company Relations'
];

// Get all roles for listing with enhanced query
function get_all_roles() {
    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT r.*, 
                   COUNT(u.id) as user_count,
                   GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) ORDER BY u.first_name SEPARATOR ', ') as users_sample
            FROM roles r 
            LEFT JOIN users u ON r.id = u.role_id 
            GROUP BY r.id
            ORDER BY 
                CASE 
                    WHEN r.is_system_role = 1 THEN 1
                    ELSE 2
                END,
                CASE r.name 
                    WHEN 'super_admin' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'principal' THEN 3
                    WHEN 'hod' THEN 4
                    WHEN 'faculty' THEN 5
                    WHEN 'student' THEN 6
                    WHEN 'parent' THEN 7
                    WHEN 'staff' THEN 8
                    WHEN 'guest' THEN 9
                    ELSE 10
                END, r.name
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get roles error: " . $e->getMessage());
        return [];
    }
}

// Get single role for editing
function get_role_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get role error: " . $e->getMessage());
        return false;
    }
}

// Enhanced handle add role function
function handle_add_role($data) {
    global $pdo;
    
    // Validate input
    $errors = [];
    
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Role name must be at least 2 characters long.";
    }
    
    if (!preg_match('/^[a-z_]+$/', $data['name'])) {
        $errors[] = "Role name can only contain lowercase letters and underscores.";
    }
    
    if (empty($data['description'])) {
        $errors[] = "Role description is required.";
    }
    
    if (empty($data['permissions']) || !is_array($data['permissions'])) {
        $errors[] = "At least one permission must be selected.";
    }
    
    // Check if it's a system role name
    $system_role_names = ['super_admin', 'admin', 'principal', 'hod', 'faculty', 'student', 'parent', 'staff', 'guest'];
    if (in_array($data['name'], $system_role_names)) {
        $errors[] = "Cannot create custom role with system role name.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate role name
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$data['name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Role name already exists.'];
        }
        
        // Insert new role
        $stmt = $pdo->prepare("
            INSERT INTO roles (name, description, permissions, is_system_role, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'],
            json_encode($data['permissions']),
            0, // Custom roles are not system roles
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            $new_role_id = $pdo->lastInsertId();
            
            // Log the action
            log_audit($_SESSION['user_id'], 'create_role', 'roles', $new_role_id, null, [
                'name' => $data['name'],
                'description' => $data['description'],
                'permissions' => $data['permissions'],
                'is_system_role' => 0
            ]);
            
            return ['success' => true, 'message' => 'Role created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create role.'];
        }
        
    } catch (Exception $e) {
        error_log("Add role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle edit role function
function handle_edit_role($id, $data) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid role ID.'];
    }
    
    // Get current role to check if it's a system role
    $current_role = get_role_by_id($id);
    if (!$current_role) {
        return ['success' => false, 'message' => 'Role not found.'];
    }
    
    // Prevent editing critical system roles
    if ($current_role['is_system_role'] && in_array($current_role['name'], ['super_admin', 'admin'])) {
        return ['success' => false, 'message' => 'Critical system roles cannot be modified.'];
    }
    
    // Validate input
    $errors = [];
    
    if (empty($data['description'])) {
        $errors[] = "Role description is required.";
    }
    
    if (empty($data['permissions']) || !is_array($data['permissions'])) {
        $errors[] = "At least one permission must be selected.";
    }
    
    // For custom roles, validate name if provided
    if (!$current_role['is_system_role'] && !empty($data['name'])) {
        if (strlen($data['name']) < 2) {
            $errors[] = "Role name must be at least 2 characters long.";
        }
        
        if (!preg_match('/^[a-z_]+$/', $data['name'])) {
            $errors[] = "Role name can only contain lowercase letters and underscores.";
        }
        
        // Check for duplicate name (excluding current role)
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ? AND id != ?");
        $stmt->execute([$data['name'], $id]);
        if ($stmt->fetch()) {
            $errors[] = "Role name already exists.";
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Update role (name is immutable for system roles)
        if ($current_role['is_system_role']) {
            $stmt = $pdo->prepare("
                UPDATE roles 
                SET description = ?, permissions = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['description'],
                json_encode($data['permissions']),
                $data['status'] ?? $current_role['status'],
                $id
            ]);
        } else {
            // For custom roles, allow name changes
            $stmt = $pdo->prepare("
                UPDATE roles 
                SET name = ?, description = ?, permissions = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([
                $data['name'] ?? $current_role['name'],
                $data['description'],
                json_encode($data['permissions']),
                $data['status'] ?? $current_role['status'],
                $id
            ]);
        }
        
        if ($result) {
            // Log the action
            log_audit($_SESSION['user_id'], 'update_role', 'roles', $id, $current_role, $data);
            
            return ['success' => true, 'message' => 'Role updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update role.'];
        }
        
    } catch (Exception $e) {
        error_log("Edit role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Enhanced handle delete role function
function handle_delete_role($id) {
    global $pdo;
    
    if (!$id) {
        return ['success' => false, 'message' => 'Invalid role ID.'];
    }
    
    try {
        // Get role info for validation
        $role = get_role_by_id($id);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }
        
        // Prevent deleting system roles
        if ($role['is_system_role']) {
            return ['success' => false, 'message' => 'System roles cannot be deleted.'];
        }
        
        // Check if role is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE role_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['user_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete role: ' . $result['user_count'] . ' users are assigned to this role.'];
        }
        
        // Delete role
        $stmt = $pdo->prepare("DELETE FROM roles WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            // Log the action
            log_audit($_SESSION['user_id'], 'delete_role', 'roles', $id, $role, null);
            
            return ['success' => true, 'message' => 'Role deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete role.'];
        }
        
    } catch (Exception $e) {
        error_log("Delete role error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

// Get data based on current action
$roles = [];
$edit_role = null;

if ($action === 'list' || $action === 'delete') {
    $roles = get_all_roles();
}

if ($action === 'edit' && $role_id) {
    $edit_role = get_role_by_id($role_id);
    if (!$edit_role) {
        $error = 'Role not found.';
        $action = 'list';
        $roles = get_all_roles();
    }
}
?>

<!-- Messages -->
<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $message; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $error; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
<!-- Add Role Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Role</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=roles&action=add" id="roleForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Role Name</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="e.g., librarian, accountant"
                               pattern="[a-z_]+"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        <div class="form-hint">Use lowercase letters and underscores only. This cannot be changed later.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Display Name</label>
                        <input type="text" name="description" class="form-control" required 
                               placeholder="e.g., Librarian, Accountant"
                               value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
                        <div class="form-hint">Human-readable name for this role.</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <div class="card">
                    <div class="card-body">
                        <!-- Search permissions -->
                        <div class="mb-3">
                            <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions...">
                        </div>
                        
                        <!-- Permission categories with enhanced grouping -->
                        <?php 
                        $permission_categories = [
                            'System Administration' => ['all', 'manage_users', 'manage_roles', 'system_settings', 'view_audit_logs', 'manage_system_backups'],
                            'Institutional Management' => ['manage_college', 'manage_departments', 'manage_programs', 'manage_branches', 'manage_regulations', 'manage_academic_years', 'manage_batches', 'manage_semesters'],
                            'Student Management' => ['manage_students', 'view_students', 'manage_student_registrations', 'view_student_registrations', 'manage_student_documents', 'view_student_documents', 'approve_student_registrations', 'manage_student_admissions', 'view_student_records'],
                            'Faculty Management' => ['manage_faculty', 'view_faculty', 'manage_faculty_assignments', 'view_faculty_assignments', 'manage_faculty_qualifications', 'manage_faculty_workload', 'approve_faculty_applications'],
                            'Course Management' => ['manage_courses', 'view_courses', 'manage_course_prerequisites', 'manage_electives', 'view_electives', 'approve_course_proposals', 'manage_course_syllabus', 'manage_course_schedule'],
                            'Assessment & Marks' => ['manage_assessments', 'grade_students', 'view_grades', 'lock_unlock_marks', 'verify_marks', 'modify_marks', 'approve_grade_changes', 'generate_transcripts'],
                            'Attendance' => ['manage_class_schedule', 'mark_attendance', 'view_attendance', 'generate_attendance_reports', 'modify_attendance', 'approve_attendance_changes'],
                            'Research & Publications' => ['manage_publications', 'view_publications', 'approve_publications', 'manage_research_projects'],
                            'Reporting & Analytics' => ['view_reports', 'generate_reports', 'view_analytics', 'export_data', 'generate_certificates', 'view_institutional_reports', 'access_financial_reports'],
                            'Communications' => ['manage_notifications', 'send_announcements', 'view_announcements', 'send_bulk_communications', 'manage_communication_templates'],
                            'Personal Access' => ['view_profile', 'edit_profile', 'change_password', 'upload_documents'],
                            'Examination Management' => ['manage_exam_schedule', 'conduct_examinations', 'manage_exam_results', 'publish_results'],
                            'Finance & Fees' => ['manage_fees', 'collect_fees', 'generate_fee_receipts', 'view_fee_reports'],
                            'Role-Specific Access' => ['view_ward_progress', 'view_ward_attendance', 'view_ward_grades', 'communicate_with_faculty', 'course_registration', 'select_electives', 'view_own_grades', 'view_own_attendance', 'download_certificates', 'view_fee_details', 'apply_for_documents', 'data_entry', 'manage_records', 'process_applications', 'verify_documents', 'generate_id_cards'],
                            'Additional Services' => ['manage_library', 'issue_books', 'manage_library_fines', 'manage_placements', 'coordinate_campus_recruitment', 'manage_company_relations']
                        ];
                        
                        foreach ($permission_categories as $category => $perms): ?>
                        <div class="mb-4 permission-category">
                            <div class="d-flex align-items-center mb-3">
                                <h5 class="mb-0 me-3"><?php echo htmlspecialchars($category); ?></h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input category-toggle" type="checkbox" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                    <label class="form-check-label text-muted">Select All</label>
                                </div>
                            </div>
                            <div class="row" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                <?php foreach ($perms as $perm): ?>
                                    <?php if (isset($available_permissions[$perm])): ?>
                                    <div class="col-md-6 col-lg-4 permission-item">
                                        <label class="form-check">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input permission-checkbox"
                                                   <?php echo (isset($_POST['permissions']) && in_array($perm, $_POST['permissions'])) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">
                                                <strong><?php echo htmlspecialchars($available_permissions[$perm]); ?></strong>
                                                <small class="text-muted d-block"><?php echo $perm; ?></small>
                                            </span>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="form-hint">Select all permissions this role should have. "Full System Access" grants all permissions.</div>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Create Role
                </button>
                <a href="dashboard.php?page=roles" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_role): ?>
<!-- Edit Role Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Role: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $edit_role['name']))); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Roles
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($edit_role['is_system_role'] && in_array($edit_role['name'], ['super_admin', 'admin'])): ?>
        <div class="alert alert-warning">
            <h4>Critical System Role</h4>
            <p>This is a critical system role that cannot be modified to maintain system security and stability.</p>
        </div>
        <a href="dashboard.php?page=roles" class="btn btn-secondary">Back to Roles</a>
        
        <?php else: ?>
        <form method="POST" action="dashboard.php?page=roles&action=edit&id=<?php echo $edit_role['id']; ?>" id="roleForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <?php if ($edit_role['is_system_role']): ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_role['name']); ?>" readonly>
                            <div class="form-hint">System role names cannot be changed.</div>
                        <?php else: ?>
                            <input type="text" name="name" class="form-control" 
                                   pattern="[a-z_]+"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? $edit_role['name']); ?>">
                            <div class="form-hint">Use lowercase letters and underscores only.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Display Name</label>
                        <input type="text" name="description" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['description'] ?? $edit_role['description']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo (($_POST['status'] ?? $edit_role['status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo (($_POST['status'] ?? $edit_role['status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label required">Permissions</label>
                <?php 
                $current_permissions = json_decode($edit_role['permissions'], true) ?: [];
                $selected_permissions = $_POST['permissions'] ?? $current_permissions;
                ?>
                
                <div class="card">
                    <div class="card-body">
                        <!-- Search permissions -->
                        <div class="mb-3">
                            <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions...">
                        </div>
                        
                        <!-- Permission categories -->
                        <?php foreach ($permission_categories as $category => $perms): ?>
                        <div class="mb-4 permission-category">
                            <div class="d-flex align-items-center mb-3">
                                <h5 class="mb-0 me-3"><?php echo htmlspecialchars($category); ?></h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input category-toggle" type="checkbox" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                    <label class="form-check-label text-muted">Select All</label>
                                </div>
                            </div>
                            <div class="row" data-category="<?php echo strtolower(str_replace([' ', '&'], ['_', 'and'], $category)); ?>">
                                <?php foreach ($perms as $perm): ?>
                                    <?php if (isset($available_permissions[$perm])): ?>
                                    <div class="col-md-6 col-lg-4 permission-item">
                                        <label class="form-check">
                                            <input type="checkbox" name="permissions[]" value="<?php echo $perm; ?>" class="form-check-input permission-checkbox"
                                                   <?php echo in_array($perm, $selected_permissions) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">
                                                <strong><?php echo htmlspecialchars($available_permissions[$perm]); ?></strong>
                                                <small class="text-muted d-block"><?php echo $perm; ?></small>
                                            </span>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Role
                </button>
                <a href="dashboard.php?page=roles" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Enhanced Roles List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Role Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=roles&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Add Role
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                    <?php
                    $permissions = json_decode($role['permissions'], true) ?: [];
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($role['is_system_role']): ?>
                                    <span class="avatar bg-blue text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 1l3 6 6 .75-4.12 4.62L17.75 19 12 16l-5.75 3 .87-6.63L3 7.75 9 7z"/>
                                        </svg>
                                    </span>
                                    <?php else: ?>
                                    <span class="avatar bg-green text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                        </svg>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                                    </div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($role['name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">
                            <?php echo htmlspecialchars($role['description']); ?>
                        </td>
                        <td>
                            <?php if ($role['is_system_role']): ?>
                                <span class="badge bg-blue">System Role</span>
                            <?php else: ?>
                                <span class="badge bg-green">Custom Role</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (in_array('all', $permissions)): ?>
                                    <span class="badge bg-red">Full Access</span>
                                <?php else: ?>
                                    <?php 
                                    $permission_count = count($permissions);
                                    if ($permission_count <= 3): 
                                        foreach (array_slice($permissions, 0, 3) as $perm):
                                    ?>
                                        <span class="badge bg-blue-lt"><?php echo htmlspecialchars($available_permissions[$perm] ?? $perm); ?></span>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <span class="badge bg-blue-lt"><?php echo $permission_count; ?> permissions</span>
                                        <button class="btn btn-sm btn-ghost-secondary" data-bs-toggle="tooltip" 
                                                title="<?php echo htmlspecialchars(implode(', ', array_map(fn($p) => $available_permissions[$p] ?? $p, $permissions))); ?>">
                                            View All
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($role['user_count'] > 0): ?>
                                <span class="badge bg-green"><?php echo $role['user_count']; ?> users</span>
                                <?php if ($role['users_sample']): ?>
                                    <div class="text-muted small mt-1">
                                        <?php 
                                        $users = explode(', ', $role['users_sample']);
                                        echo htmlspecialchars(implode(', ', array_slice($users, 0, 2)));
                                        if (count($users) > 2) echo '...';
                                        ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No users</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_class = match($role['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($role['status']); ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($role['created_at'])); ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=roles&action=edit&id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <?php if (!$role['is_system_role']): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=roles&action=edit&id=<?php echo $role['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit Role
                                        </a>
                                        
                                        <?php if ($role['user_count'] == 0): ?>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete Role
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="badge bg-blue-lt">System</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No roles found. <a href="dashboard.php?page=roles&action=add">Create the first custom role</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Enhanced Role Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count($roles); ?></div>
                <div class="d-flex mb-2">
                    <div>All system roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">System Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => $r['is_system_role'])); ?></div>
                <div class="d-flex mb-2">
                    <div>Built-in roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Custom Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => !$r['is_system_role'])); ?></div>
                <div class="d-flex mb-2">
                    <div>User-created roles</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Roles</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($roles, fn($r) => $r['status'] === 'active')); ?></div>
                <div class="d-flex mb-2">
                    <div>Currently active</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteRoleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete role <strong id="deleteRoleName"></strong>? This action cannot be undone.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteRoleForm" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="submit" class="btn btn-danger">Yes, delete role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, 'dashboard.php?page=roles');
}

// Confirm delete role
function confirmDeleteRole(roleId, roleName) {
    document.getElementById('deleteRoleName').textContent = roleName;
    document.getElementById('deleteRoleForm').action = `dashboard.php?page=roles&action=delete&id=${roleId}`;
    const modal = new bootstrap.Modal(document.getElementById('deleteRoleModal'));
    modal.show();
}

// Enhanced form validation and functionality
document.addEventListener('DOMContentLoaded', function() {
    const roleForm = document.getElementById('roleForm');
    if (roleForm) {
        // Form validation
        roleForm.addEventListener('submit', function(e) {
            // Check if at least one permission is selected
            const permissionCheckboxes = roleForm.querySelectorAll('input[name="permissions[]"]');
            if (permissionCheckboxes.length > 0) {
                const checkedPermissions = roleForm.querySelectorAll('input[name="permissions[]"]:checked');
                if (checkedPermissions.length === 0) {
                    e.preventDefault();
                    showMessage('danger', 'Please select at least one permission for this role.');
                    return;
                }
            }
            
            // Validate required fields
            const requiredFields = roleForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showMessage('danger', 'Please fill in all required fields.');
            }
        });
    }
    
    // Permission search functionality
    const permissionSearch = document.getElementById('permissionSearch');
    if (permissionSearch) {
        permissionSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const permissionItems = document.querySelectorAll('.permission-item');
            const categories = document.querySelectorAll('.permission-category');
            
            permissionItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                item.style.display = shouldShow ? 'block' : 'none';
            });
            
            // Hide categories that have no visible permissions
            categories.forEach(category => {
                const visibleItems = category.querySelectorAll('.permission-item[style*="display: block"], .permission-item:not([style*="display: none"])');
                category.style.display = visibleItems.length > 0 ? 'block' : 'none';
            });
        });
    }
    
    // Category toggle functionality
    const categoryToggles = document.querySelectorAll('.category-toggle');
    categoryToggles.forEach(toggle => {
        const categoryName = toggle.dataset.category;
        const categoryDiv = document.querySelector(`[data-category="${categoryName}"]`);
        
        if (categoryDiv) {
            const checkboxes = categoryDiv.querySelectorAll('.permission-checkbox');
            
            // Set initial state of toggle based on selected checkboxes
            const checkedCount = categoryDiv.querySelectorAll('.permission-checkbox:checked').length;
            const totalCount = checkboxes.length;
            
            if (checkedCount === totalCount && totalCount > 0) {
                toggle.checked = true;
            } else if (checkedCount > 0) {
                toggle.indeterminate = true;
            }
            
            // Handle toggle change
            toggle.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                this.indeterminate = false;
            });
            
            // Update toggle state when individual checkboxes change
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checkedCount = categoryDiv.querySelectorAll('.permission-checkbox:checked').length;
                    const totalCount = checkboxes.length;
                    
                    if (checkedCount === 0) {
                        toggle.checked = false;
                        toggle.indeterminate = false;
                    } else if (checkedCount === totalCount) {
                        toggle.checked = true;
                        toggle.indeterminate = false;
                    } else {
                        toggle.checked = false;
                        toggle.indeterminate = true;
                    }
                });
            });
        }
    });
    
    // Handle "select all" functionality for full access permission
    const fullAccessCheckbox = document.querySelector('input[value="all"]');
    const otherCheckboxes = document.querySelectorAll('input[name="permissions[]"]:not([value="all"])');
    
    if (fullAccessCheckbox) {
        fullAccessCheckbox.addEventListener('change', function() {
            if (this.checked) {
                otherCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
                // Also disable category toggles
                categoryToggles.forEach(toggle => {
                    toggle.checked = false;
                    toggle.disabled = true;
                });
            } else {
                otherCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
                categoryToggles.forEach(toggle => {
                    toggle.disabled = false;
                });
            }
        });
        
        // Initial state
        if (fullAccessCheckbox.checked) {
            otherCheckboxes.forEach(cb => {
                cb.disabled = true;
            });
            categoryToggles.forEach(toggle => {
                toggle.disabled = true;
            });
        }
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add input event listeners to remove invalid class when user starts typing
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Enhanced message display function
function showMessage(type, message) {
    const existingMessages = document.querySelectorAll('.alert.auto-message');
    existingMessages.forEach(msg => msg.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible auto-message fade show`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
    `;
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : type === 'info' ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.auto-message)');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-dismissible')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }
        });
    }, 5000);
});
</script>