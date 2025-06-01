<!-- Sidebar Navigation -->
        <aside class="navbar navbar-vertical navbar-expand-lg navbar-light" id="sidebar">
            <div class="container-fluid">
                <!-- Sidebar Header with Toggle -->
                <div class="navbar-brand d-flex justify-content-between align-items-center">
                    <span class="sidebar-title">Navigation</span>
                    <button class="btn btn-ghost-secondary btn-sm" id="sidebarToggle" title="Minimize Sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="11,17 6,12 11,7"></polyline>
                            <polyline points="18,17 13,12 18,7"></polyline>
                        </svg>
                    </button>
                </div>
                
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav sidebar-nav pt-lg-3">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'home') ? 'active' : ''; ?>" href="dashboard.php" title="Dashboard">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Dashboard</span>
                            </a>
                        </li>

                        <!-- Profile -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'profile') ? 'active' : ''; ?>" href="dashboard.php?page=profile" title="My Profile">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </span>
                                <span class="nav-link-title">My Profile</span>
                            </a>
                        </li>

                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department' || $_SESSION['role'] === 'faculty')): ?>
                        <!-- Students -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo ($current_page === 'students') ? 'active' : ''; ?>" href="#navbar-students" data-bs-toggle="dropdown" role="button" title="Students Management">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="8.5" cy="7" r="4"></circle>
                                        <line x1="20" y1="8" x2="20" y2="14"></line>
                                        <line x1="23" y1="11" x2="17" y2="11"></line>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Students</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="dashboard.php?page=students">All Students</a>
                                <a class="dropdown-item" href="dashboard.php?page=students&action=add">Add Student</a>
                                <a class="dropdown-item" href="dashboard.php?page=students&action=grades">Grades</a>
                            </div>
                        </li>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                        <!-- Faculty -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo ($current_page === 'faculty') ? 'active' : ''; ?>" href="#navbar-faculty" data-bs-toggle="dropdown" role="button" title="Faculty Management">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Faculty</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="dashboard.php?page=faculty">All Faculty</a>
                                <a class="dropdown-item" href="dashboard.php?page=faculty&action=add">Add Faculty</a>
                                <a class="dropdown-item" href="dashboard.php?page=faculty&action=departments">Departments</a>
                            </div>
                        </li>
                        <?php endif; ?>

                        <!-- Courses -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo ($current_page === 'courses') ? 'active' : ''; ?>" href="#navbar-courses" data-bs-toggle="dropdown" role="button" title="Courses Management">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Courses</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="dashboard.php?page=courses">All Courses</a>
                                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                                <a class="dropdown-item" href="dashboard.php?page=courses&action=add">Add Course</a>
                                <?php endif; ?>
                                <a class="dropdown-item" href="dashboard.php?page=courses&action=schedule">Schedule</a>
                            </div>
                        </li>

                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                        <!-- Reports -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'reports') ? 'active' : ''; ?>" href="dashboard.php?page=reports" title="Reports">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10,9 9,9 8,9"></polyline>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Reports</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                        <!-- Administration -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo ($current_page === 'users') ? 'active' : ''; ?>" href="#navbar-admin" data-bs-toggle="dropdown" role="button" title="Administration">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Administration</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="dashboard.php?page=users">User Management</a>
                                <a class="dropdown-item" href="dashboard.php?page=settings">System Settings</a>
                                <a class="dropdown-item" href="test_auth.php">Debug Tools</a>
                            </div>
                        </li>
                        <?php endif; ?>

                        <!-- Divider -->
                        <li class="nav-item">
                            <div class="hr-text">Account</div>
                        </li>

                        <!-- Settings -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page === 'settings') ? 'active' : ''; ?>" href="dashboard.php?page=settings" title="Settings">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Settings</span>
                            </a>
                        </li>

                        <!-- Logout -->
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php" title="Logout">
                                <span class="nav-link-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16,17 21,12 16,7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                </span>
                                <span class="nav-link-title">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Minimize Toggle Button (when minimized) -->
            <div class="sidebar-minimized-toggle" id="sidebarExpandBtn" style="display: none;">
                <button class="btn btn-primary btn-sm" title="Expand Sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="13,17 18,12 13,7"></polyline>
                        <polyline points="6,17 11,12 6,7"></polyline>
                    </svg>
                </button>
            </div>
        </aside>