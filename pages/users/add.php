<?php
// pages/users/add.php - Add User Form

// Check permissions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to add users.</p>
          </div>';
    return;
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $result = handle_add_user($_POST);
        if ($result['success']) {
            header('Location: dashboard.php?page=users&success=' . urlencode($result['message']));
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Get roles for dropdown
$roles = get_all_active_roles();

function handle_add_user($data) {
    $pdo = get_database_connection();
    
    // Validate input
    $validation_errors = validate_user_data($data);
    
    if (!empty($validation_errors)) {
        return ['success' => false, 'message' => implode('<br>', $validation_errors)];
    }
    
    try {
        // Check for duplicate username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already exists.'];
        }
        
        // Generate UUID for user
        $user_uuid = generate_uuid();
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (user_uuid, username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_uuid,
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role_id'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active'
        ]);
        
        if ($result) {
            $new_user_id = $pdo->lastInsertId();
            
            // Log the action
            log_audit($_SESSION['user_id'], 'create_user', 'users', $new_user_id, null, [
                'username' => $data['username'],
                'email' => $data['email'],
                'role_id' => $data['role_id']
            ]);
            
            return ['success' => true, 'message' => 'User created successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
        
    } catch (Exception $e) {
        error_log("Add user error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()];
    }
}

function validate_user_data($data, $is_update = false) {
    $errors = [];
    
    if (!$is_update || isset($data['username'])) {
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = "Username must be at least 3 characters long.";
        }
        
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['username'])) {
            $errors[] = "Username can only contain letters, numbers, dots, underscores, and hyphens.";
        }
    }
    
    if (!$is_update || isset($data['email'])) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email address is required.";
        }
    }
    
    if (!$is_update || isset($data['password'])) {
        if (!$is_update && empty($data['password'])) {
            $errors[] = "Password is required.";
        } elseif (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors[] = "Password must be at least 8 characters long.";
            }
        }
    }
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($data['role_id']) || !is_numeric($data['role_id'])) {
        $errors[] = "Valid role selection is required.";
    }
    
    return $errors;
}

function get_all_active_roles() {
    $pdo = get_database_connection();
    try {
        $stmt = $pdo->query("SELECT * FROM roles WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get roles error: " . $e->getMessage());
        return [];
    }
}
?>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div>
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Add User Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New User</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=users" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Users
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=users&action=add" id="addUserForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               pattern="[a-zA-Z0-9._-]+"
                               title="Username can only contain letters, numbers, dots, underscores, and hyphens">
                        <div class="form-hint">Username must be unique and at least 3 characters long.</div>
                        <div class="invalid-feedback">Please provide a valid username.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <div class="form-hint">Email must be unique and valid.</div>
                        <div class="invalid-feedback">Please provide a valid email address.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">First Name</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        <div class="invalid-feedback">Please provide a first name.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        <div class="invalid-feedback">Please provide a last name.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" required minlength="8"
                                   id="password">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long.</div>
                        <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Role</label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" 
                                    <?php echo (($_POST['role_id'] ?? '') == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                                <?php if ($role['description']): ?>
                                    - <?php echo htmlspecialchars($role['description']); ?>
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a role.</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                               pattern="[0-9+\-\s()]*"
                               title="Please enter a valid phone number">
                        <div class="form-hint">Optional phone number</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo (($_POST['status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3" 
                          placeholder="Enter complete address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                <div class="form-hint">Optional address information</div>
            </div>
            
            <!-- Password Strength Indicator -->
            <div class="mb-3" id="passwordStrength" style="display: none;">
                <label class="form-label">Password Strength</label>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div class="form-hint" id="passwordStrengthText">Enter a password to see strength</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Create User
                </button>
                <button type="reset" class="btn btn-secondary ms-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6v6h6"></path>
                        <path d="M21 18v-6h-6"></path>
                        <path d="M9 6a9 9 0 0 1 9 9"></path>
                        <path d="M15 18a9 9 0 0 1-9-9"></path>
                    </svg>
                    Reset Form
                </button>
                <a href="dashboard.php?page=users" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Form Guidelines -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">User Creation Guidelines</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h4>Username Requirements</h4>
                <ul>
                    <li>Must be at least 3 characters long</li>
                    <li>Can contain letters, numbers, dots, underscores, and hyphens</li>
                    <li>Must be unique across the system</li>
                    <li>Cannot be changed after creation</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h4>Password Security</h4>
                <ul>
                    <li>Minimum 8 characters long</li>
                    <li>Should include uppercase and lowercase letters</li>
                    <li>Should include numbers and special characters</li>
                    <li>Users can change their password after first login</li>
                </ul>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h4>Role Selection</h4>
                <ul>
                    <li><strong>Super Admin:</strong> Full system access</li>
                    <li><strong>Admin:</strong> Administrative functions</li>
                    <li><strong>Principal:</strong> Institutional oversight</li>
                    <li><strong>HOD:</strong> Department management</li>
                    <li><strong>Faculty:</strong> Teaching and course management</li>
                    <li><strong>Student:</strong> Learning and course access</li>
                    <li><strong>Parent:</strong> Student progress monitoring</li>
                    <li><strong>Staff:</strong> Administrative support</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h4>Best Practices</h4>
                <ul>
                    <li>Use institutional email addresses when possible</li>
                    <li>Ensure contact information is accurate</li>
                    <li>Set appropriate initial status (active/pending)</li>
                    <li>Consider using bulk import for multiple users</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addUserForm');
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('passwordStrength');
    const strengthBar = strengthIndicator.querySelector('.progress-bar');
    const strengthText = document.getElementById('passwordStrengthText');
    
    // Real-time form validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            }
        });
        
        // Additional validation
        const username = form.querySelector('[name="username"]');
        if (username.value && !username.value.match(/^[a-zA-Z0-9._-]+$/)) {
            username.classList.add('is-invalid');
            isValid = false;
        }
        
        const email = form.querySelector('[name="email"]');
        if (email.value && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            email.classList.add('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showMessage('danger', 'Please fix the errors in the form before submitting.');
            return false;
        }
    });
    
    // Real-time password strength checking
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) {
            strengthIndicator.style.display = 'none';
            return;
        }
        
        strengthIndicator.style.display = 'block';
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthDisplay(strength);
    });
    
    // Remove invalid class when user starts typing
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
    });
    
    function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) score += 20;
        else feedback.push('Use at least 8 characters');
        
        if (password.length >= 12) score += 10;
        
        // Character variety
        if (/[a-z]/.test(password)) score += 20;
        else feedback.push('Add lowercase letters');
        
        if (/[A-Z]/.test(password)) score += 20;
        else feedback.push('Add uppercase letters');
        
        if (/[0-9]/.test(password)) score += 20;
        else feedback.push('Add numbers');
        
        if (/[^a-zA-Z0-9]/.test(password)) score += 10;
        else feedback.push('Add special characters');
        
        return {
            score: Math.min(score, 100),
            feedback: feedback
        };
    }
    
    function updatePasswordStrengthDisplay(strength) {
        let color, text;
        
        if (strength.score < 40) {
            color = 'bg-danger';
            text = 'Weak';
        } else if (strength.score < 70) {
            color = 'bg-warning';
            text = 'Fair';
        } else if (strength.score < 90) {
            color = 'bg-info';
            text = 'Good';
        } else {
            color = 'bg-success';
            text = 'Strong';
        }
        
        strengthBar.className = `progress-bar ${color}`;
        strengthBar.style.width = strength.score + '%';
        strengthText.textContent = `${text} - ${strength.feedback.join(', ')}`;
    }
});

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    } else {
        input.type = 'password';
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        `;
    }
}

// Message display function
function showMessage(type, message) {
    const existingMessages = document.querySelectorAll('.alert.auto-message');
    existingMessages.forEach(msg => msg.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible auto-message fade show`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
    `;
    
    const iconPath = type === 'success' 
        ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'
        : type === 'info' 
        ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'
        : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z';
    
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}
</script>