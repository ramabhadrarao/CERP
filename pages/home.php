<?php
// pages/home.php - Dashboard home content

// Get basic stats
try {
    $stats = [];
    
    if ($user['role_name'] === 'super_admin' || $user['role_name'] === 'head_of_department') {
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
        $stats['total_students'] = $stmt->fetch()['total_students'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_faculty FROM faculty");
        $stats['total_faculty'] = $stmt->fetch()['total_faculty'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_courses FROM courses");
        $stats['total_courses'] = $stmt->fetch()['total_courses'] ?? 0;
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_departments FROM departments");
        $stats['total_departments'] = $stmt->fetch()['total_departments'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $stats = [];
}
?>

<!-- Dashboard Stats -->
<div class="row row-deck row-cards mb-4">
    <?php if ($user['role_name'] === 'super_admin' || $user['role_name'] === 'head_of_department'): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Students</div>
                </div>
                <div class="h1 mb-3"><?php echo number_format($stats['total_students'] ?? 0); ?></div>
                <div class="d-flex mb-2">
                    <div>Active enrollment</div>
                    <div class="ms-auto">
                        <span class="text-green d-inline-flex align-items-center lh-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                            </svg>
                            12%
                        </span>
                    </div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-blue" style="width: 75%" role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Faculty Members</div>
                </div>
                <div class="h1 mb-3"><?php echo number_format($stats['total_faculty'] ?? 0); ?></div>
                <div class="d-flex mb-2">
                    <div>Active faculty</div>
                    <div class="ms-auto">
                        <span class="text-green d-inline-flex align-items-center lh-1">8%</span>
                    </div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-green" style="width: 85%" role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Courses</div>
                </div>
                <div class="h1 mb-3"><?php echo number_format($stats['total_courses'] ?? 0); ?></div>
                <div class="d-flex mb-2">
                    <div>This semester</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-yellow" style="width: 60%" role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Departments</div>
                </div>
                <div class="h1 mb-3"><?php echo number_format($stats['total_departments'] ?? 0); ?></div>
                <div class="d-flex mb-2">
                    <div>Active departments</div>
                </div>
                <div class="progress progress-sm">
                    <div class="progress-bar bg-purple" style="width: 100%" role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($user['role_name'] === 'student'): ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">My Courses</div>
                </div>
                <div class="h1 mb-3">5</div>
                <div class="d-flex mb-2">
                    <div>Current semester</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">GPA</div>
                </div>
                <div class="h1 mb-3">3.8</div>
                <div class="d-flex mb-2">
                    <div>Current GPA</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Attendance</div>
                </div>
                <div class="h1 mb-3">92%</div>
                <div class="d-flex mb-2">
                    <div>This month</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($user['role_name'] === 'faculty'): ?>
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">My Courses</div>
                </div>
                <div class="h1 mb-3">3</div>
                <div class="d-flex mb-2">
                    <div>Teaching this semester</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">My Students</div>
                </div>
                <div class="h1 mb-3">85</div>
                <div class="d-flex mb-2">
                    <div>Under supervision</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending Grades</div>
                </div>
                <div class="h1 mb-3">12</div>
                <div class="d-flex mb-2">
                    <div>To be graded</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Welcome Card -->
<div class="row row-deck row-cards">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Welcome to School Management System</h3>
            </div>
            <div class="card-body">
                <h4>Hello, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h4>
                <p class="text-muted">You are logged in as: <strong><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></strong></p>
                
                <?php if ($user['role_name'] === 'super_admin'): ?>
                <div class="alert alert-info">
                    <h4>Administrator Dashboard</h4>
                    <p>You have full access to the system. You can manage users, view reports, and configure system settings.</p>
                    <ul>
                        <li>Manage students, faculty, and other users</li>
                        <li>View comprehensive reports and analytics</li>
                        <li>Configure system settings and permissions</li>
                        <li>Monitor system activity and audit logs</li>
                    </ul>
                </div>
                <?php elseif ($user['role_name'] === 'faculty'): ?>
                <div class="alert alert-success">
                    <h4>Faculty Dashboard</h4>
                    <p>Welcome to your faculty dashboard. You can manage your courses and students.</p>
                    <ul>
                        <li>View and manage your assigned courses</li>
                        <li>Grade students and track their progress</li>
                        <li>Access student information and records</li>
                    </ul>
                </div>
                <?php elseif ($user['role_name'] === 'student'): ?>
                <div class="alert alert-primary">
                    <h4>Student Dashboard</h4>
                    <p>Welcome to your student portal. You can view your courses and grades.</p>
                    <ul>
                        <li>View your enrolled courses</li>
                        <li>Check your grades and GPA</li>
                        <li>Update your profile information</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php if ($user['role_name'] === 'super_admin' || $user['role_name'] === 'head_of_department'): ?>
                    <div class="col-6">
                        <a href="dashboard.php?page=students&action=add" class="btn btn-outline-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            <br><small>Add Student</small>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-6">
                        <a href="dashboard.php?page=profile" class="btn btn-outline-secondary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <br><small>My Profile</small>
                        </a>
                    </div>
                    
                    <div class="col-6">
                        <a href="dashboard.php?page=courses" class="btn btn-outline-info w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                            <br><small>Courses</small>
                        </a>
                    </div>
                    
                    <?php if ($user['role_name'] === 'super_admin' || $user['role_name'] === 'head_of_department'): ?>
                    <div class="col-6">
                        <a href="dashboard.php?page=reports" class="btn btn-outline-warning w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" class="mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                            </svg>
                            <br><small>Reports</small>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">System Status</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h3 text-green">✅</div>
                            <div class="text-muted">Database</div>
                            <div class="small">Connected</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="h3 text-blue">🔒</div>
                            <div class="text-muted">Security</div>
                            <div class="small">Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>