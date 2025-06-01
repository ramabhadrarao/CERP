<!-- Tabler UI Sidebar Navigation -->
<div class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar navbar-light">
            <div class="container-xl">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <rect x="4" y="4" width="6" height="6" rx="1"></rect>
                                    <rect x="14" y="4" width="6" height="6" rx="1"></rect>
                                    <rect x="4" y="14" width="6" height="6" rx="1"></rect>
                                    <rect x="14" y="14" width="6" height="6" rx="1"></rect>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Home
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Navigation with Tabler UI Structure -->
<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark" id="sidebar">
    <div class="container-fluid">
        <!-- Sidebar Header -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="dashboard.php" class="d-flex align-items-center text-decoration-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="110" height="32" viewBox="0 0 232 68" class="navbar-brand-image">
                    <path d="M64 32C64 49.673 49.673 64 32 64C14.327 64 0 49.673 0 32C0 14.327 14.327 0 32 0C49.673 0 64 14.327 64 32Z" fill="var(--tblr-primary)"/>
                    <path d="M32 8C40.837 8 48 15.163 48 24C48 32.837 40.837 40 32 40C23.163 40 16 32.837 16 24C16 15.163 23.163 8 32 8Z" fill="white"/>
                    <text x="80" y="24" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="600" fill="currentColor">School</text>
                    <text x="80" y="44" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="600" fill="currentColor">Management</text>
                </svg>
            </a>
        </h1>
        
        <!-- Sidebar Controls -->
        <div class="navbar-nav flex-row d-lg-none">
            <div class="nav-item d-none d-lg-flex me-3">
                <div class="btn-list">
                    <button class="btn btn-ghost-secondary btn-sm" id="sidebarToggle" title="Minimize Sidebar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="11,17 6,12 11,7"></polyline>
                            <polyline points="18,17 13,12 18,7"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Menu -->
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                
                <!-- Dashboard -->
                <li class="nav-item <?php echo ($current_page === 'home') ? 'active' : ''; ?>">
                    <a class="nav-link" href="dashboard.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="4" y="4" width="6" height="6" rx="1"/>
                                <rect x="14" y="4" width="6" height="6" rx="1"/>
                                <rect x="4" y="14" width="6" height="6" rx="1"/>
                                <rect x="14" y="14" width="6" height="6" rx="1"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Dashboard
                        </span>
                    </a>
                </li>
                
                <!-- Profile -->
                <li class="nav-item <?php echo ($current_page === 'profile') ? 'active' : ''; ?>">
                    <a class="nav-link" href="dashboard.php?page=profile">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="12" cy="7" r="4"/>
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            My Profile
                        </span>
                    </a>
                </li>
                
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department' || $_SESSION['role'] === 'faculty')): ?>
                <!-- Students Menu -->
                <li class="nav-item dropdown <?php echo ($current_page === 'students') ? 'active' : ''; ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-students" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Students
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item <?php echo ($current_page === 'students' && !isset($_GET['action'])) ? 'active' : ''; ?>" href="dashboard.php?page=students">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                        </svg>
                                    </span>
                                    All Students
                                </a>
                                <?php if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department'): ?>
                                <a class="dropdown-item <?php echo ($current_page === 'students' && isset($_GET['action']) && $_GET['action'] === 'add') ? 'active' : ''; ?>" href="dashboard.php?page=students&action=add">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </span>
                                    Add Student
                                </a>
                                <?php endif; ?>
                                <a class="dropdown-item <?php echo ($current_page === 'students' && isset($_GET['action']) && $_GET['action'] === 'grades') ? 'active' : ''; ?>" href="dashboard.php?page=students&action=grades">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                            <line x1="9" y1="9" x2="10" y2="9"/>
                                            <line x1="9" y1="13" x2="15" y2="13"/>
                                            <line x1="9" y1="17" x2="15" y2="17"/>
                                        </svg>
                                    </span>
                                    Grades & Results
                                </a>
                                <a class="dropdown-item" href="dashboard.php?page=students&action=attendance">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="4" y="5" width="16" height="16" rx="2"/>
                                            <line x1="16" y1="3" x2="16" y2="7"/>
                                            <line x1="8" y1="3" x2="8" y2="7"/>
                                            <line x1="4" y1="11" x2="20" y2="11"/>
                                            <path d="M11 15h1"/>
                                            <path d="M12 15v3"/>
                                        </svg>
                                    </span>
                                    Attendance
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                <!-- Faculty Menu -->
                <li class="nav-item dropdown <?php echo ($current_page === 'faculty') ? 'active' : ''; ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-faculty" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                <path d="M21 21v-2a4 4 0 0 0 -3 -3"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Faculty
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item <?php echo ($current_page === 'faculty' && !isset($_GET['action'])) ? 'active' : ''; ?>" href="dashboard.php?page=faculty">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                        </svg>
                                    </span>
                                    All Faculty
                                </a>
                                <a class="dropdown-item <?php echo ($current_page === 'faculty' && isset($_GET['action']) && $_GET['action'] === 'add') ? 'active' : ''; ?>" href="dashboard.php?page=faculty&action=add">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </span>
                                    Add Faculty
                                </a>
                                <a class="dropdown-item <?php echo ($current_page === 'faculty' && isset($_GET['action']) && $_GET['action'] === 'departments') ? 'active' : ''; ?>" href="dashboard.php?page=faculty&action=departments">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 21l18 0"/>
                                            <path d="M5 21v-16l2.5 0c.276 0 .5 -.224 .5 -.5s.224 -.5 .5 -.5l2.5 0c.276 0 .5 .224 .5 .5s.224 .5 .5 .5l2.5 0c.276 0 .5 -.224 .5 -.5s.224 -.5 .5 -.5l2.5 0c.276 0 .5 .224 .5 .5s.224 .5 .5 .5l2.5 0v16"/>
                                            <line x1="9" y1="21" x2="9" y2="7"/>
                                            <line x1="15" y1="21" x2="15" y2="7"/>
                                        </svg>
                                    </span>
                                    Departments
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Courses Menu -->
                <li class="nav-item dropdown <?php echo ($current_page === 'courses') ? 'active' : ''; ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-courses" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                <line x1="3" y1="6" x2="3" y2="19"/>
                                <line x1="12" y1="6" x2="12" y2="19"/>
                                <line x1="21" y1="6" x2="21" y2="19"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Courses
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <a class="dropdown-item <?php echo ($current_page === 'courses' && !isset($_GET['action'])) ? 'active' : ''; ?>" href="dashboard.php?page=courses">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                            <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/>
                                            <line x1="3" y1="6" x2="3" y2="19"/>
                                            <line x1="12" y1="6" x2="12" y2="19"/>
                                            <line x1="21" y1="6" x2="21" y2="19"/>
                                        </svg>
                                    </span>
                                    All Courses
                                </a>
                                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                                <a class="dropdown-item <?php echo ($current_page === 'courses' && isset($_GET['action']) && $_GET['action'] === 'add') ? 'active' : ''; ?>" href="dashboard.php?page=courses&action=add">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                    </span>
                                    Add Course
                                </a>
                                <?php endif; ?>
                                <a class="dropdown-item <?php echo ($current_page === 'courses' && isset($_GET['action']) && $_GET['action'] === 'schedule') ? 'active' : ''; ?>" href="dashboard.php?page=courses&action=schedule">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="4" y="5" width="16" height="16" rx="2"/>
                                            <line x1="16" y1="3" x2="16" y2="7"/>
                                            <line x1="8" y1="3" x2="8" y2="7"/>
                                            <line x1="4" y1="11" x2="20" y2="11"/>
                                        </svg>
                                    </span>
                                    Schedule
                                </a>
                                <a class="dropdown-item" href="dashboard.php?page=courses&action=syllabus">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>
                                            <line x1="9" y1="9" x2="10" y2="9"/>
                                            <line x1="9" y1="13" x2="15" y2="13"/>
                                            <line x1="9" y1="17" x2="15" y2="17"/>
                                        </svg>
                                    </span>
                                    Syllabus
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                <!-- Reports -->
                <li class="nav-item <?php echo ($current_page === 'reports') ? 'active' : ''; ?>">
                    <a class="nav-link" href="dashboard.php?page=reports">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                <path d="M21 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                <path d="M19 12h2m-2 0h-2m-14 0h2m-2 0h-2"/>
                                <path d="M12 10v-7a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v18a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1v-7"/>
                                <path d="M12 14v7a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1v-18a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v7"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Reports
                        </span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                <!-- Administration Menu -->
                <li class="nav-item dropdown <?php echo (in_array($current_page, ['users', 'roles', 'settings'])) ? 'active' : ''; ?>">
                    <a class="nav-link dropdown-toggle" href="#navbar-administration" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Administration
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="dropdown-menu-columns">
                            <div class="dropdown-menu-column">
                                <h6 class="dropdown-header">User Management</h6>
                                <a class="dropdown-item <?php echo ($current_page === 'users') ? 'active' : ''; ?>" href="dashboard.php?page=users">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                            <path d="M21 21v-2a4 4 0 0 0 -3 -3"/>
                                        </svg>
                                    </span>
                                    Manage Users
                                </a>
                                <a class="dropdown-item <?php echo ($current_page === 'roles') ? 'active' : ''; ?>" href="dashboard.php?page=roles">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 6l4 6l5 -4l-2 10h-14l-2 -10l5 4z"/>
                                        </svg>
                                    </span>
                                    Roles & Permissions
                                </a>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">System</h6>
                                <a class="dropdown-item" href="dashboard.php?page=settings">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>
                                    System Settings
                                </a>
                                <a class="dropdown-item" href="dashboard.php?page=audit">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>
                                    Audit Logs
                                </a>
                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header">Development</h6>
                                <a class="dropdown-item" href="test_auth.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M7 8l-4 4l4 4"/>
                                            <path d="M17 8l4 4l-4 4"/>
                                            <path d="M14 4l-4 16"/>
                                        </svg>
                                    </span>
                                    Debug Tools
                                </a>
                                <a class="dropdown-item" href="test-popup.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <line x1="9" y1="9" x2="15" y2="15"/>
                                            <line x1="15" y1="9" x2="9" y2="15"/>
                                        </svg>
                                    </span>
                                    Test Popups
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Divider -->
                <li class="nav-item">
                    <div class="hr-text hr-text-center hr-text-spaceless">Account</div>
                </li>
                
                <!-- Account Settings -->
                <li class="nav-item <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
                    <a class="nav-link" href="dashboard.php?page=settings">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Settings
                        </span>
                    </a>
                </li>
                
                <!-- Logout -->
                <li class="nav-item">
                    <a class="nav-link text-red" href="logout.php">
                        <span class="nav-link-icon d-md-none d-lg-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/>
                                <path d="M7 12h14l-3 -3m0 6l3 -3"/>
                            </svg>
                        </span>
                        <span class="nav-link-title">
                            Logout
                        </span>
                    </a>
                </li>
                
            </ul>
        </div>
    </div>
</aside>

<!-- Enhanced CSS for Tabler UI Navigation -->
<style>
/* Tabler UI Navigation Styles */
.navbar-vertical {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    z-index: var(--z-sidebar, 1030) !important;
    background: var(--tblr-navbar-bg, #182433);
    border-right: var(--tblr-border-width) solid var(--tblr-border-color);
    transition: all 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
}

.navbar-vertical .navbar-brand {
    padding: 1rem 1.5rem;
    border-bottom: var(--tblr-border-width) solid var(--tblr-border-color-translucent);
}

.navbar-vertical .navbar-nav {
    padding: 0.5rem 0;
}

.navbar-vertical .nav-item {
    margin: 0 0.75rem 0.25rem;
}

.navbar-vertical .nav-link {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    color: var(--tblr-navbar-color);
    border-radius: var(--tblr-border-radius);
    transition: all 0.2s ease;
    text-decoration: none;
    font-weight: 500;
}

.navbar-vertical .nav-link:hover {
    background-color: var(--tblr-navbar-hover-color);
    color: var(--tblr-navbar-active-color);
}

.navbar-vertical .nav-item.active > .nav-link,
.navbar-vertical .nav-link.active {
    background-color: var(--tblr-primary);
    color: white;
    box-shadow: var(--tblr-box-shadow-sm);
}

.navbar-vertical .nav-link-icon {
    margin-right: 0.75rem;
    width: 1.25rem;
    height: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.navbar-vertical .nav-link-title {
    flex: 1;
}

/* Dropdown Menu Styling */
.navbar-vertical .dropdown-menu {
    position: static;
    float: none;
    border: none;
    border-radius: 0;
    box-shadow: none;
    background: transparent;
    padding: 0;
    margin: 0.25rem 0 0;
    width: 100%;
    display: none;
}

.navbar-vertical .nav-item.show .dropdown-menu {
    display: block;
}

.navbar-vertical .dropdown-item {
    padding: 0.375rem 0.75rem 0.375rem 2.5rem;
    color: var(--tblr-navbar-color);
    border-radius: var(--tblr-border-radius);
    margin: 0 0.75rem 0.125rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

.navbar-vertical .dropdown-item:hover {
    background-color: var(--tblr-navbar-hover-color);
    color: var(--tblr-navbar-active-color);
}

.navbar-vertical .dropdown-item.active {
    background-color: var(--tblr-primary);
    color: white;
}

.navbar-vertical .dropdown-item .nav-link-icon {
    margin-right: 0.5rem;
    width: 1rem;
    height: 1rem;
}

/* Dropdown Headers */
.navbar-vertical .dropdown-header {
    padding: 0.5rem 0.75rem 0.25rem 2.5rem;
    margin: 0 0.75rem;
    color: var(--tblr-navbar-color);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.navbar-vertical .dropdown-divider {
    margin: 0.5rem 0.75rem;
    border-color: var(--tblr-border-color-translucent);
}

/* Dropdown Toggle Arrow */
.navbar-vertical .dropdown-toggle::after {
    margin-left: auto;
    border: none;
    content: "\ea6e";
    font-family: "tabler-icons";
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.navbar-vertical .nav-item.show .dropdown-toggle::after {
    transform: rotate(90deg);
}

/* HR Text Styling */
.navbar-vertical .hr-text {
    margin: 1rem 0.75rem 0.5rem;
    color: var(--tblr-navbar-color);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.navbar-vertical .hr-text::before,
.navbar-vertical .hr-text::after {
    border-color: var(--tblr-border-color-translucent);
}

/* Minimized Sidebar State */
.navbar-vertical.minimized {
    width: 4rem;
}

.navbar-vertical.minimized .nav-link-title,
.navbar-vertical.minimized .navbar-brand svg text,
.navbar-vertical.minimized .hr-text,
.navbar-vertical.minimized .dropdown-toggle::after {
    display: none;
}

.navbar-vertical.minimized .nav-link {
    justify-content: center;
    padding: 0.75rem;
}

.navbar-vertical.minimized .nav-link-icon {
    margin-right: 0;
}

.navbar-vertical.minimized .dropdown-menu {
    position: fixed;
    left: 4rem;
    top: auto;
    background: var(--tblr-navbar-bg);
    border: var(--tblr-border-width) solid var(--tblr-border-color);
    border-radius: var(--tblr-border-radius);
    box-shadow: var(--tblr-box-shadow);
    padding: 0.5rem 0;
    min-width: 12rem;
    z-index: calc(var(--z-dropdown, 1050) + 10);
}

.navbar-vertical.minimized .dropdown-item {
    padding: 0.375rem 0.75rem;
    margin: 0 0.25rem;
}

.navbar-vertical.minimized .dropdown-header {
    padding: 0.5rem 0.75rem 0.25rem;
    margin: 0 0.25rem;
}

.navbar-vertical.minimized .dropdown-divider {
    margin: 0.5rem 0.25rem;
}

/* Mobile Responsiveness */
@media (max-width: 992px) {
    .navbar-vertical {
        left: -280px;
        z-index: calc(var(--z-modal, 1060) + 10);
    }
    
    .navbar-vertical.show {
        left: 0;
        box-shadow: var(--tblr-box-shadow-lg);
    }
}

/* Page wrapper adjustment */
.page-wrapper {
    margin-left: 280px;
    transition: margin-left 0.3s ease;
}

.sidebar-minimized .page-wrapper {
    margin-left: 4rem;
}

@media (max-width: 992px) {
    .page-wrapper {
        margin-left: 0;
    }
}

/* Brand logo styling */
.navbar-brand-image text {
    font-size: 14px;
    font-weight: 600;
}

/* Active state improvements */
.navbar-vertical .nav-item.active > .nav-link {
    background: linear-gradient(135deg, var(--tblr-primary) 0%, var(--tblr-primary-darker) 100%);
    color: white;
    box-shadow: 0 0.125rem 0.25rem rgba(var(--tblr-primary-rgb), 0.3);
}

/* Smooth animations */
.navbar-vertical .nav-link,
.navbar-vertical .dropdown-item {
    transition: all 0.15s ease-in-out;
}

/* Icon consistency */
.navbar-vertical .icon {
    stroke-width: 1.5;
}
</style>

<script>
// Enhanced Tabler UI Navigation JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.navbar-vertical');
    const dropdowns = sidebar.querySelectorAll('[data-bs-toggle="dropdown"]');
    
    // Initialize dropdown functionality
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            
            const navItem = this.closest('.nav-item');
            const dropdownMenu = navItem.querySelector('.dropdown-menu');
            
            // Close other dropdowns
            dropdowns.forEach(otherDropdown => {
                const otherNavItem = otherDropdown.closest('.nav-item');
                if (otherNavItem !== navItem) {
                    otherNavItem.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            navItem.classList.toggle('show');
        });
    });
    
    // Handle minimized sidebar hover dropdowns
    sidebar.addEventListener('mouseenter', function(e) {
        if (this.classList.contains('minimized')) {
            const navItem = e.target.closest('.nav-item.dropdown');
            if (navItem) {
                const dropdownMenu = navItem.querySelector('.dropdown-menu');
                if (dropdownMenu) {
                    const rect = navItem.getBoundingClientRect();
                    dropdownMenu.style.position = 'fixed';
                    dropdownMenu.style.left = '4rem';
                    dropdownMenu.style.top = rect.top + 'px';
                    dropdownMenu.style.display = 'block';
                }
            }
        }
    });
    
    sidebar.addEventListener('mouseleave', function() {
        if (this.classList.contains('minimized')) {
            const dropdownMenus = this.querySelectorAll('.dropdown-menu');
            dropdownMenus.forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    // Set active states based on current page
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
    const navLinks = sidebar.querySelectorAll('.nav-link, .dropdown-item');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (
            (currentPage === 'home' && href === 'dashboard.php') ||
            (href.includes('page=' + currentPage))
        )) {
            // Set active state
            const navItem = link.closest('.nav-item');
            navItem.classList.add('active');
            
            // If it's a dropdown item, also show the parent dropdown
            const parentDropdown = link.closest('.dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('show');
                const parentToggle = parentDropdown.querySelector('.dropdown-toggle');
                if (parentToggle) {
                    parentToggle.closest('.nav-item').classList.add('active');
                }
            }
        }
    });
    
    console.log('Tabler UI Navigation initialized');
});
</script>