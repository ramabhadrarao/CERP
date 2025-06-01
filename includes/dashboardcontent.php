<!-- Enhanced Main Content Area for Tabler UI Integration -->

<div class="page-wrapper content-wrapper">
    <div class="page-header d-print-none">
        <div class="container-fluid">
            <?php
            // Enhanced dynamic page header based on current page with improved Tabler UI styling
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
                    
                    // Show notifications button with count and enhanced styling
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
                                <div class="page-pretitle">Account</div>
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
                                    <button class="btn btn-primary" onclick="showEditProfilePopup()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Edit Profile
                                    </button>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                case 'students':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Management</div>
                                <h2 class="page-title">Student Management</h2>
                                <div class="text-muted mt-1">Manage student records and information</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>';
                    if (in_array('manage_students', $_SESSION['permissions']) || in_array('all', $_SESSION['permissions'])) {
                        echo '<button class="btn btn-success" onclick="showBulkImportPopup()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10,9 9,9 8,9"></polyline>
                                </svg>
                                Bulk Import
                              </button>
                              <a href="dashboard.php?page=students&action=add" class="btn btn-primary">
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
                    
                case 'faculty':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Management</div>
                                <h2 class="page-title">Faculty Management</h2>
                                <div class="text-muted mt-1">Manage faculty members and assignments</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>';
                    if (in_array('manage_faculty', $_SESSION['permissions']) || in_array('all', $_SESSION['permissions'])) {
                        echo '<button class="btn btn-info" onclick="showFacultyReportsPopup()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14,2 14,8 20,8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10,9 9,9 8,9"></polyline>
                                </svg>
                                Reports
                              </button>
                              <a href="dashboard.php?page=faculty&action=add" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Faculty
                              </a>';
                    }
                    echo '</div>
                            </div>
                          </div>';
                    break;
                    
                case 'users':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Administration</div>
                                <h2 class="page-title">User Management</h2>
                                <div class="text-muted mt-1">Manage system users and permissions</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>
                                    <button class="btn btn-warning" onclick="showUserAuditPopup()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="3"></circle>
                                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                        </svg>
                                        Audit Logs
                                    </button>
                                    <a href="dashboard.php?page=users&action=add" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        Add User
                                    </a>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                case 'roles':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Administration</div>
                                <h2 class="page-title">Role Management</h2>
                                <div class="text-muted mt-1">Manage user roles and permissions</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>
                                    <button class="btn btn-info" onclick="showPermissionMatrixPopup()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <rect x="7" y="7" width="3" height="9"></rect>
                                            <rect x="14" y="7" width="3" height="5"></rect>
                                        </svg>
                                        Permission Matrix
                                    </button>
                                    <a href="dashboard.php?page=roles&action=add" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        Add Role
                                    </a>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                case 'settings':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Account</div>
                                <h2 class="page-title">Settings</h2>
                                <div class="text-muted mt-1">Manage your account preferences</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="15,18 9,12 15,6"></polyline>
                                        </svg>
                                        Back to Dashboard
                                    </a>
                                    <button class="btn btn-warning" onclick="showBackupSystemPopup()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="10,8 16,12 10,16"></polyline>
                                        </svg>
                                        System Backup
                                    </button>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                default:
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Navigation</div>
                                <h2 class="page-title">' . htmlspecialchars($page_title) . '</h2>
                                <div class="text-muted mt-1">Manage your ' . strtolower($page_title) . '</div>
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
            }
            ?>
        </div>
    </div>

    <!-- Enhanced Page Body with Better Error Handling -->
    <div class="page-body">
        <div class="container-fluid">
            <?php
            // Enhanced error handling and loading states
            try {
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
                    case 'gender':
                        include 'pages/gender.php';
                        break;
                    case '403':
                        include 'pages/403.php';
                        break;
                    default:
                        include 'pages/404.php';
                        break;
                }
            } catch (Exception $e) {
                // Enhanced error display with Tabler UI styling
                error_log("Dashboard content error: " . $e->getMessage());
                echo '<div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 9v2m0 4v.01"/>
                                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                                </svg>
                            </div>
                            <h3>Page Loading Error</h3>
                            <p class="text-muted">An error occurred while loading the page content. Please try again.</p>
                            <div class="btn-list">
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="23,4 23,10 17,10"></polyline>
                                        <polyline points="1,20 1,14 7,14"></polyline>
                                        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                                    </svg>
                                    Reload Page
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    </svg>
                                    Go to Dashboard
                                </a>
                            </div>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
</div>

<?php
// Enhanced helper functions for improved dashboard functionality

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
        case 'super_admin':
            return "Welcome to the system administration panel.";
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
    } elseif ($diff < 2592000) { // Less than 30 days
        $days = floor($diff / 86400);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y g:i A', $timestamp);
    }
}
?>

<script>
// Enhanced popup functions for dashboard actions
function showEditProfilePopup() {
    createTablerPopup('Edit Profile', `
        <div class="alert alert-info">
            <h4>Profile Editing</h4>
            <p>This would open a comprehensive profile editing form with all user details.</p>
        </div>
        <div class="btn-list">
            <button class="btn btn-primary" onclick="closeTablerPopup()">Got it</button>
        </div>
    `);
}

function showBulkImportPopup() {
    createTablerPopup('Bulk Import Students', `
        <div class="mb-3">
            <p>Import multiple students from a CSV file.</p>
            <div class="form-group">
                <label class="form-label">CSV File</label>
                <input type="file" class="form-control" accept=".csv" id="bulkImportFile">
                <div class="form-hint">File should contain: first_name, last_name, email, student_id, department</div>
            </div>
        </div>
        <div class="alert alert-warning">
            <h4>Import Guidelines</h4>
            <ul class="mb-0">
                <li>CSV file must have headers</li>
                <li>Email addresses must be unique</li>
                <li>Student IDs must be unique</li>
                <li>Maximum 1000 records per import</li>
            </ul>
        </div>
        <div class="btn-list">
            <button class="btn btn-primary" onclick="processBulkImport()">Import Students</button>
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Cancel</button>
        </div>
    `);
}

function showFacultyReportsPopup() {
    createTablerPopup('Faculty Reports', `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-body">
                        <h4>Department Summary</h4>
                        <p class="text-muted">Faculty distribution by department</p>
                        <button class="btn btn-sm btn-primary">Generate</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-body">
                        <h4>Workload Analysis</h4>
                        <p class="text-muted">Teaching hours and course assignments</p>
                        <button class="btn btn-sm btn-primary">Generate</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-body">
                        <h4>Qualification Report</h4>
                        <p class="text-muted">Faculty qualifications and experience</p>
                        <button class="btn btn-sm btn-primary">Generate</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-sm">
                    <div class="card-body">
                        <h4>Performance Metrics</h4>
                        <p class="text-muted">Teaching evaluations and feedback</p>
                        <button class="btn btn-sm btn-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
        </div>
    `);
}

function showUserAuditPopup() {
    createTablerPopup('User Audit Logs', `
        <div class="mb-3">
            <div class="form-group">
                <label class="form-label">Filter by Action</label>
                <select class="form-select">
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="create_user">Create User</option>
                    <option value="update_user">Update User</option>
                    <option value="delete_user">Delete User</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date Range</label>
                <div class="row">
                    <div class="col-6">
                        <input type="date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="col-6">
                        <input type="date" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>12:34 PM</td>
                        <td>admin</td>
                        <td><span class="badge bg-green">login</span></td>
                        <td>192.168.1.100</td>
                    </tr>
                    <tr>
                        <td>12:30 PM</td>
                        <td>admin</td>
                        <td><span class="badge bg-blue">create_user</span></td>
                        <td>192.168.1.100</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="btn-list">
            <button class="btn btn-primary">Export Logs</button>
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
        </div>
    `);
}

function showPermissionMatrixPopup() {
    createTablerPopup('Permission Matrix', `
        <div class="alert alert-info">
            <h4>Role Permissions Overview</h4>
            <p>This matrix shows which permissions are assigned to each role.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Super Admin</th>
                        <th>Admin</th>
                        <th>Faculty</th>
                        <th>Student</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Manage Users</td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-red">✗</span></td>
                        <td><span class="badge bg-red">✗</span></td>
                    </tr>
                    <tr>
                        <td>View Students</td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-red">✗</span></td>
                    </tr>
                    <tr>
                        <td>Edit Profile</td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                        <td><span class="badge bg-green">✓</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="btn-list">
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
        </div>
    `);
}

function showBackupSystemPopup() {
    createTablerPopup('System Backup', `
        <div class="alert alert-warning">
            <h4>Database Backup</h4>
            <p>Create a backup of the current system state including all user data, settings, and configurations.</p>
        </div>
        <div class="mb-3">
            <div class="form-group">
                <label class="form-label">Backup Type</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="backup_type" value="full" checked>
                    <label class="form-check-label">Full Backup (All data)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="backup_type" value="partial">
                    <label class="form-check-label">Partial Backup (Configuration only)</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Backup Description</label>
                <input type="text" class="form-control" placeholder="e.g., Weekly backup before system update">
            </div>
        </div>
        <div class="btn-list">
            <button class="btn btn-warning" onclick="startBackupProcess()">Create Backup</button>
            <button class="btn btn-secondary" onclick="closeTablerPopup()">Cancel</button>
        </div>
    `);
}

function processBulkImport() {
    const fileInput = document.getElementById('bulkImportFile');
    if (!fileInput.files[0]) {
        showMessage('warning', 'Please select a CSV file to import.');
        return;
    }
    
    updateTablerPopup('Processing Import', `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <h4>Processing CSV File</h4>
            <p class="text-muted">Validating and importing student records...</p>
            <div class="progress mt-3">
                <div class="progress-bar progress-bar-indeterminate"></div>
            </div>
        </div>
    `);
    
    // Simulate processing
    setTimeout(() => {
        updateTablerPopup('Import Complete', `
            <div class="alert alert-success">
                <h4>Import Successful!</h4>
                <p>Successfully imported 45 student records.</p>
                <ul>
                    <li>45 new students added</li>
                    <li>0 duplicates skipped</li>
                    <li>0 validation errors</li>
                </ul>
            </div>
            <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
        `);
    }, 3000);
}

function startBackupProcess() {
    updateTablerPopup('Creating Backup', `
        <div class="text-center">
            <div class="spinner-border text-warning mb-3" role="status">
                <span class="visually-hidden">Creating backup...</span>
            </div>
            <h4>Creating System Backup</h4>
            <p class="text-muted">This may take a few minutes...</p>
            <div class="progress mt-3">
                <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" style="width: 65%"></div>
            </div>
        </div>
    `);
    
    // Simulate backup process
    setTimeout(() => {
        updateTablerPopup('Backup Complete', `
            <div class="alert alert-success">
                <h4>Backup Created Successfully!</h4>
                <p>Your system backup has been created and saved.</p>
                <div class="row mt-3">
                    <div class="col-6">
                        <div class="subheader">File Size</div>
                        <div>42.3 MB</div>
                    </div>
                    <div class="col-6">
                        <div class="subheader">Location</div>
                        <div>/backups/backup_${new Date().toISOString().split('T')[0]}.sql</div>
                    </div>
                </div>
            </div>
            <div class="btn-list">
                <button class="btn btn-success">Download Backup</button>
                <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
            </div>
        `);
    }, 4000);
}

// Enhanced dashboard initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any dashboard-specific components
    console.log('Enhanced dashboard content loaded');
    
    // Add any page-specific initialization here
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
    console.log('Current page:', currentPage);
    
    // Initialize tooltips for any new elements
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>