<?php
// profile.php - User profile page
require_once 'config/database.php';
require_once 'includes/auth.php';

require_login();

$current_page = 'profile';
$page_title = 'My Profile';

// Get user details
$pdo = get_database_connection();
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Page header content
ob_start();
?>
<div class="row align-items-center">
    <div class="col">
        <h2 class="page-title">My Profile</h2>
        <div class="text-muted mt-1">View and manage your profile information</div>
    </div>
    <div class="col-auto ms-auto">
        <div class="btn-list">
            <a href="edit-profile.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Profile
            </a>
        </div>
    </div>
</div>
<?php
$page_header = ob_get_clean();

// Page content
ob_start();
?>

<div class="row row-deck row-cards">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-blue-lt fs-6"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'])); ?></span>
                
                <div class="mt-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-primary">
                                    <?php 
                                    $status_icon = match($user['status']) {
                                        'active' => '✅',
                                        'inactive' => '⚪',
                                        'suspended' => '🔴',
                                        default => '⚪'
                                    };
                                    echo $status_icon;
                                    ?>
                                </div>
                                <div class="text-muted">Status</div>
                                <div class="small"><?php echo ucfirst($user['status']); ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-success">📅</div>
                                <div class="text-muted">Member Since</div>
                                <div class="small"><?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Role Information -->
        <?php if ($user['role_name'] === 'student'): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Academic Information</h3>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT s.*, d.name as department_name 
                    FROM students s 
                    LEFT JOIN departments d ON s.department_id = d.id 
                    WHERE s.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $student_info = $stmt->fetch();
                
                if ($student_info):
                ?>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Student ID</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($student_info['student_id']); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Department</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($student_info['department_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Semester</label>
                        <div class="form-control-plaintext"><?php echo $student_info['semester'] ?: 'N/A'; ?></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Year of Admission</label>
                        <div class="form-control-plaintext"><?php echo $student_info['year_of_admission'] ?: 'N/A'; ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($user['role_name'] === 'faculty'): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Faculty Information</h3>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT f.*, d.name as department_name 
                    FROM faculty f 
                    LEFT JOIN departments d ON f.department_id = d.id 
                    WHERE f.user_id = ?
                ");
                $stmt->execute([$user['id']]);
                $faculty_info = $stmt->fetch();
                
                if ($faculty_info):
                ?>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Employee ID</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($faculty_info['employee_id']); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Department</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($faculty_info['department_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Designation</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($faculty_info['designation'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Qualification</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($faculty_info['qualification'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['first_name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['last_name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-blue"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'])); ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Account Information</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Account Status</label>
                        <div class="form-control-plaintext">
                            <?php
                            $status_class = match($user['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                'suspended' => 'bg-red',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($user['status']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Member Since</label>
                        <div class="form-control-plaintext"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Updated</label>
                        <div class="form-control-plaintext"><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">User ID</label>
                        <div class="form-control-plaintext">#<?php echo $user['id']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-auto">
                        <a href="change-password.php" class="btn btn-outline-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <circle cx="12" cy="16" r="1"></circle>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Change Password
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="logout.php" class="btn btn-outline-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16,17 21,12 16,7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the layout
include 'includes/layout.php';
?>