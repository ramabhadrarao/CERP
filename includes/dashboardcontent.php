<!-- Main Content Area -->

<div class="page-wrapper content-wrapper">
    <div class="page-header d-print-none">
        <div class="container-fluid">
            <?php
            // Dynamic page header based on current page
            switch ($current_page) {
                case 'home':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <div class="page-pretitle">Welcome back</div>
                                <h2 class="page-title">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</h2>
                                <div class="text-muted mt-1">
                                    <span class="role-badge bg-blue-lt">' . ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')) . '</span>
                                    <span class="ms-2 text-muted">Last login: ' . date('M j, Y g:i A', $_SESSION['login_time'] ?? time()) . '</span>
                                </div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php?page=profile" class="btn btn-outline-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        Profile
                                    </a>
                                </div>
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
                    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')) {
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
                    
                case 'faculty':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">Faculty Management</h2>
                                <div class="text-muted mt-1">Manage faculty members and departments</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>';
                    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')) {
                        echo '<a href="dashboard.php?page=faculty&action=add" class="btn btn-primary">
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
                    
                case 'courses':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">Course Management</h2>
                                <div class="text-muted mt-1">Manage courses and schedules</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>';
                    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')) {
                        echo '<a href="dashboard.php?page=courses&action=add" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Course
                              </a>';
                    }
                    echo '</div>
                            </div>
                          </div>';
                    break;
                    
                case 'reports':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">Reports</h2>
                                <div class="text-muted mt-1">Generate and view system reports</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                            </svg>
                                            Generate Report
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#">Student Report</a>
                                            <a class="dropdown-item" href="#">Faculty Report</a>
                                            <a class="dropdown-item" href="#">Course Report</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                          </div>';
                    break;
                    
                case 'users':
                    echo '<div class="row align-items-center">
                            <div class="col">
                                <h2 class="page-title">User Management</h2>
                                <div class="text-muted mt-1">Manage system users and their access</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                    <a href="dashboard.php?page=users&action=add" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="8.5" cy="7" r="4"></circle>
                                            <line x1="20" y1="8" x2="20" y2="14"></line>
                                            <line x1="23" y1="11" x2="17" y2="11"></line>
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
                                <h2 class="page-title">Role Management</h2>
                                <div class="text-muted mt-1">Manage user roles and permissions</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                    <a href="dashboard.php?page=roles&action=add" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 1l3 6 6 .75-4.12 4.62L17.75 19 12 16l-5.75 3 .87-6.63L3 7.75 9 7z"/>
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
                                <h2 class="page-title">Settings</h2>
                                <div class="text-muted mt-1">Configure your account and system preferences</div>
                            </div>
                            <div class="col-auto ms-auto">
                                <div class="btn-list">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                </div>
                            </div>
                          </div>';
                    break;
                    
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

    <!-- Page Body -->
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
                default:
                    include 'pages/404.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>