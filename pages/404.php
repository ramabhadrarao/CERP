<?php
// pages/404.php - Page not found content
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="display-1 text-muted mb-4">🚫</div>
                <h1 class="h2 mb-3">Page Not Found</h1>
                <p class="text-muted mb-4">
                    Sorry, the page you are looking for doesn't exist or you don't have permission to access it.
                </p>
                
                <div class="alert alert-info text-start">
                    <h4>Available Pages:</h4>
                    <ul class="mb-0">
                        <li><a href="dashboard.php">Dashboard</a> - Main dashboard</li>
                        <li><a href="dashboard.php?page=profile">Profile</a> - Your profile information</li>
                        <li><a href="dashboard.php?page=settings">Settings</a> - Account settings</li>
                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department' || $_SESSION['role'] === 'faculty')): ?>
                        <li><a href="dashboard.php?page=students">Students</a> - Student management</li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'head_of_department')): ?>
                        <li><a href="dashboard.php?page=faculty">Faculty</a> - Faculty management</li>
                        <li><a href="dashboard.php?page=reports">Reports</a> - System reports</li>
                        <?php endif; ?>
                        <li><a href="dashboard.php?page=courses">Courses</a> - Course information</li>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                        <li><a href="dashboard.php?page=users">Users</a> - User management</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="btn-list">
                    <a href="dashboard.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        </svg>
                        Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>