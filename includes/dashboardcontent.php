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
                        default:
                            include 'pages/404.php';
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>