<?php
// pages/profile.php - Enhanced profile page for new schema

// Get comprehensive user details with new schema
$additional_info = null;
$educational_details = [];
$documents = [];
$qualifications = [];
$work_experience = [];

try {
    if ($user['role_name'] === 'student') {
        // Enhanced student query with new comprehensive schema
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   b.name as batch_name, b.start_year, b.end_year,
                   p.name as program_name, p.code as program_code, p.degree_type,
                   br.name as branch_name, br.code as branch_code,
                   d.name as department_name, d.code as department_code,
                   g.name as gender,
                   bg.name as blood_group,
                   st.name as student_type,
                   rel.name as religion,
                   c.name as caste, sc.name as sub_caste,
                   n.name as nationality,
                   sem.name as current_semester_name, sem.semester_number,
                   reg.name as regulation_name,
                   ay.name as academic_year
            FROM students s
            LEFT JOIN batches b ON s.batch_id = b.id
            LEFT JOIN programs p ON s.program_id = p.id
            LEFT JOIN branches br ON s.branch_id = br.id
            LEFT JOIN departments d ON p.department_id = d.id
            LEFT JOIN gender g ON s.gender_id = g.id
            LEFT JOIN blood_groups bg ON s.blood_group_id = bg.id
            LEFT JOIN student_types st ON s.student_type_id = st.id
            LEFT JOIN religion rel ON s.religion_id = rel.id
            LEFT JOIN caste c ON s.caste_id = c.id
            LEFT JOIN sub_caste sc ON s.sub_caste_id = sc.id
            LEFT JOIN nationality n ON s.nationality_id = n.id
            LEFT JOIN semesters sem ON s.current_semester_id = sem.id
            LEFT JOIN regulations reg ON s.regulation_id = reg.id
            LEFT JOIN academic_years ay ON ay.is_current = 1
            WHERE s.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $additional_info = $stmt->fetch();
        
        if ($additional_info) {
            // Get educational details
            $stmt = $pdo->prepare("
                SELECT sed.*, d.name as district_name, s.name as state_name
                FROM student_educational_details sed
                LEFT JOIN districts d ON sed.district_id = d.id
                LEFT JOIN states s ON sed.state_id = s.id
                WHERE sed.student_id = ?
                ORDER BY sed.year_of_passing DESC
            ");
            $stmt->execute([$additional_info['id']]);
            $educational_details = $stmt->fetchAll();
            
            // Get additional documents
            $stmt = $pdo->prepare("
                SELECT sad.*, u.first_name as verified_by_name, u.last_name as verified_by_lastname
                FROM student_additional_documents sad
                LEFT JOIN users u ON sad.verified_by = u.id
                WHERE sad.student_id = ? 
                ORDER BY sad.uploaded_date DESC
            ");
            $stmt->execute([$additional_info['id']]);
            $documents = $stmt->fetchAll();
        }
        
    } elseif ($user['role_name'] === 'faculty') {
        // Enhanced faculty query with new comprehensive schema
        $stmt = $pdo->prepare("
            SELECT f.*, fad.*,
                   d.name as department_name, d.code as department_code,
                   g.name as gender,
                   bg.name as blood_group,
                   n.name as nationality,
                   rel.name as religion,
                   c.name as caste, sc.name as sub_caste
            FROM faculty f
            LEFT JOIN faculty_additional_details fad ON f.id = fad.faculty_id
            LEFT JOIN departments d ON f.department_id = d.id
            LEFT JOIN gender g ON f.gender_id = g.id
            LEFT JOIN blood_groups bg ON f.blood_group_id = bg.id
            LEFT JOIN nationality n ON fad.nationality_id = n.id
            LEFT JOIN religion rel ON fad.religion_id = rel.id
            LEFT JOIN caste c ON fad.caste_id = c.id
            LEFT JOIN sub_caste sc ON fad.sub_caste_id = sc.id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $additional_info = $stmt->fetch();
        
        if ($additional_info) {
            // Get faculty qualifications
            $stmt = $pdo->prepare("
                SELECT * FROM faculty_qualifications 
                WHERE faculty_id = ? 
                ORDER BY passing_year DESC
            ");
            $stmt->execute([$additional_info['id']]);
            $qualifications = $stmt->fetchAll();
            
            // Get work experience
            $stmt = $pdo->prepare("
                SELECT * FROM faculty_work_experience 
                WHERE faculty_id = ? 
                ORDER BY from_date DESC
            ");
            $stmt->execute([$additional_info['id']]);
            $work_experience = $stmt->fetchAll();
        }
    }
} catch (Exception $e) {
    error_log("Error loading profile info: " . $e->getMessage());
}
?>

<div class="row row-deck row-cards">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <!-- Enhanced profile photo section -->
                <div class="position-relative d-inline-block mb-3">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars(PROFILE_PHOTO_PATH . $user['profile_photo']); ?>" 
                             alt="Profile Photo" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <div class="user-avatar-large mx-auto">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Photo upload button -->
                    <button class="btn btn-sm btn-outline-primary position-absolute bottom-0 end-0 rounded-circle" 
                            onclick="document.getElementById('photoUpload').click()" title="Change Photo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                    </button>
                    <input type="file" id="photoUpload" accept="image/*" style="display: none;" onchange="uploadProfilePhoto(this)">
                </div>
                
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge bg-blue-lt fs-6"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></span>
                
                <!-- Enhanced status display -->
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
                                        'pending' => '🟡',
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
        
        <!-- Enhanced Role-specific Information -->
        <?php if ($user['role_name'] === 'student' && $additional_info): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Academic Information</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Admission Number</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['admission_no']); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Registration Number</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['regd_no'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Program</label>
                        <div class="form-control-plaintext">
                            <?php echo htmlspecialchars($additional_info['program_name']); ?>
                            <?php if ($additional_info['degree_type']): ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($additional_info['degree_type']); ?>)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Branch</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['branch_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Department</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['department_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Current Semester</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['current_semester_name'] ?: 'Not Set'); ?></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Batch</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['batch_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Student Type</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['student_type'] ?: 'Regular'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Personal Details for Students -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Personal Details</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <?php if ($additional_info['dob']): ?>
                    <div class="col-6">
                        <label class="form-label">Date of Birth</label>
                        <div class="form-control-plaintext"><?php echo date('M j, Y', strtotime($additional_info['dob'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($additional_info['gender']): ?>
                    <div class="col-6">
                        <label class="form-label">Gender</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['gender']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($additional_info['blood_group']): ?>
                    <div class="col-6">
                        <label class="form-label">Blood Group</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['blood_group']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($additional_info['nationality']): ?>
                    <div class="col-6">
                        <label class="form-label">Nationality</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['nationality']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($additional_info['religion']): ?>
                    <div class="col-6">
                        <label class="form-label">Religion</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['religion']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($additional_info['caste']): ?>
                    <div class="col-6">
                        <label class="form-label">Caste</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['caste']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php elseif ($user['role_name'] === 'faculty' && $additional_info): ?>
        <!-- Faculty Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Faculty Information</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Employee ID</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['employee_id']); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Faculty Type</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['faculty_type'] ?: 'Regular'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Department</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['department_name'] ?: 'Not Assigned'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Designation</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['designation'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Qualification</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($additional_info['qualification'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Experience</label>
                        <div class="form-control-plaintext"><?php echo $additional_info['experience_years'] ? $additional_info['experience_years'] . ' years' : 'N/A'; ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Join Date</label>
                        <div class="form-control-plaintext"><?php echo $additional_info['join_date'] ? date('M j, Y', strtotime($additional_info['join_date'])) : 'N/A'; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <!-- Basic Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
                <div class="card-actions">
                    <a href="edit-profile.php" class="btn btn-sm btn-primary">Edit Profile</a>
                </div>
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
                        <div class="form-control-plaintext">
                            <?php echo htmlspecialchars($user['email']); ?>
                            <?php if (EMAIL_VERIFICATION_REQUIRED): ?>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge bg-green ms-1">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-yellow ms-1">Unverified</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-blue"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Educational Details for Students -->
        <?php if (!empty($educational_details)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Educational Background</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Course/Board</th>
                                <th>Institution</th>
                                <th>Year</th>
                                <th>Grade/Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($educational_details as $edu): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($edu['education_level']); ?></td>
                                <td><?php echo htmlspecialchars($edu['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($edu['school_college_name']); ?></td>
                                <td><?php echo $edu['year_of_passing']; ?></td>
                                <td><?php echo htmlspecialchars($edu['percentage_cgpa']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Qualifications for Faculty -->
        <?php if (!empty($qualifications)): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Qualifications</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Degree</th>
                                <th>Specialization</th>
                                <th>Institution</th>
                                <th>Year</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qualifications as $qual): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($qual['degree']); ?>
                                    <?php if ($qual['is_highest_qualification']): ?>
                                        <span class="badge bg-gold ms-1">Highest</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($qual['specialization']); ?></td>
                                <td><?php echo htmlspecialchars($qual['institution']); ?></td>
                                <td><?php echo $qual['passing_year']; ?></td>
                                <td><?php echo htmlspecialchars($qual['percentage_cgpa']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-auto">
                        <a href="dashboard.php?page=settings" class="btn btn-outline-primary">
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

<script>
// Enhanced profile photo upload
function uploadProfilePhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select a valid image file.');
            return;
        }
        
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }
        
        const formData = new FormData();
        formData.append('profile_photo', file);
        formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
        
        // Show loading indicator
        const photoContainer = input.parentElement;
        const originalContent = photoContainer.innerHTML;
        photoContainer.innerHTML = '<div class="spinner-border" role="status"><span class="sr-only">Uploading...</span></div>';
        
        fetch('upload-profile-photo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show new photo
                window.location.reload();
            } else {
                alert('Upload failed: ' + (data.message || 'Unknown error'));
                photoContainer.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Upload failed. Please try again.');
            photoContainer.innerHTML = originalContent;
        });
    }
}
</script>