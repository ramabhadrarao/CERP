<!-- Enhanced Main Content Area for New Schema -->

<div class="page-wrapper content-wrapper">
    <div class="page-header d-print-none">
        <div class="container-fluid">
            <?php
            // Enhanced dynamic page header based on current page
            switch ($current_page) {
                case 'home':
                    $welcome_message = get_role_based_welcome_message($user, $role_specific_data);
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Welcome back</div>
                                <h2 class="page-title">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</h2>
                                <div class="text-muted mt-1">
                                    <span class="role-badge bg-blue-lt">' . ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')) . '</span>
                                    <span class="ms-2 text-muted">Last login: ' . format_last_login($user['last_login']) . '</span>';
                    
                    // Show additional role-specific info
                    if (isset($role_specific_data['student_info'])) {
                        echo '<br><small class="text-muted">' . htmlspecialchars($role_specific_data['student_info']['program_name'] ?? '') . ' - ' . htmlspecialchars($role_specific_data['student_info']['current_semester_name'] ?? '') . '</small>';
                    } elseif (isset($role_specific_data['faculty_info'])) {
                        echo '<br><small class="text-muted">' . htmlspecialchars($role_specific_data['faculty_info']['department_name'] ?? '') . ' Department</small>';
                    }
                    
                    echo '</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php?page=profile" class="btn btn-outline-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        Profile
                                    </a>';
                    
                    // Show notifications button with count
                    if ($role_specific_data['unread_notifications'] > 0) {
                        echo '<a href="dashboard.php?page=notifications" class="btn btn-outline-warning position-relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                Notifications
                                <span class="badge bg-red ms-1">' . $role_specific_data['unread_notifications'] . '</span>
                              </a>';
                    }
                    
                    echo '      </div>
                            </div>
                          </div>';
                    break;
                    
                case 'profile':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">My Profile</h2>
                                <div class="text-muted mt-1">View and manage your profile information</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                case 'students':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">Student Management</h2>
                                <div class="text-muted mt-1">Manage student records and information</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>';
                    if (in_array('manage_students', $_SESSION['permissions']) || in_array('all', $_SESSION['permissions'])) {
                        echo '<a href="dashboard.php?page=students&action=add" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Student
                              </a>';
                    }
                    echo '</div>
                            </div>
                          </div>';
                    break;
                    
                // Add more enhanced page headers as needed...
                
                default:
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">' . htmlspecialchars($page_title) . '</h2>
                                <div class="text-muted mt-1">Manage your ' . strtolower($page_title) . '</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                </div>
                            </div>
                          </div>';
                    break;
            }
            ?>
        </div>
    </div>

    <!-- Enhanced Page Body -->
    <div class="page-body">
        <div class="container-fluid">
            <?php
            // Include the appropriate content based on current page
            switch ($current_page) {
                case 'home':
                    include 'pages/home.php';
                    break;
                case 'profile':
                    include 'pages/profile.php';
                    break;
                case 'students':
                    include 'pages/students.php';
                    break;
                case 'faculty':
                    include 'pages/faculty.php';
                    break;
                case 'courses':
                    include 'pages/courses.php';
                    break;
                case 'reports':
                    include 'pages/reports.php';
                    break;
                case 'settings':
                    include 'pages/settings.php';
                    break;
                case 'users':
                    include 'pages/users.php';
                    break;
                case 'roles':
                    include 'pages/roles.php';
                    break;
                case 'departments':
                    include 'pages/departments.php';
                    break;
                case 'programs':
                    include 'pages/programs.php';
                    break;
                case 'batches':
                    include 'pages/batches.php';
                    break;
                case 'regulations':
                    include 'pages/regulations.php';
                    break;
                case 'academic-years':
                    include 'pages/academic-years.php';
                    break;
                case 'notifications':
                    include 'pages/notifications.php';
                    break;
                case 'my-courses':
                    include 'pages/my-courses.php';
                    break;
                case 'ward-progress':
                    include 'pages/ward-progress.php';
                    break;
                case 'department':
                    include 'pages/department-overview.php';
                    break;
                case 'records':
                    include 'pages/records.php';
                    break;
                case '403':
                    include 'pages/403.php';
                    break;
                default:
                    include 'pages/404.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php
// Helper functions for enhanced dashboard

function get_role_based_welcome_message($user, $role_data) {
    switch ($user['role_name']) {
        case 'student':
            return "Welcome to your student portal. Check your courses, grades, and attendance.";
        case 'faculty':
            return "Welcome to your faculty dashboard. Manage your courses and students.";
        case 'hod':
            return "Welcome to the department management dashboard.";
        case 'principal':
            return "Welcome to the institutional dashboard.";
        default:
            return "Welcome to the educational management system.";
    }
}

function format_last_login($last_login) {
    if (!$last_login) {
        return 'First login';
    }
    
    $timestamp = strtotime($last_login);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 3600) { // Less than 1 hour
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) { // Less than 1 day
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y g:i A', $timestamp);
    }
}
?>