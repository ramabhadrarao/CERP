<?php
// pages/settings.php - Settings page content with inline password change

// Handle password change submission
$password_success = '';
$password_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $password_error = 'Invalid request. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $password_error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $password_error = 'New password must be at least 8 characters long.';
        } else {
            // Verify current password
            try {
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $current_user = $stmt->fetch();
                
                if (!$current_user || !password_verify($current_password, $current_user['password_hash'])) {
                    $password_error = 'Current password is incorrect.';
                } else {
                    // Update password
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                    $result = $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                    
                    if ($result) {
                        // Log the action
                        log_audit($_SESSION['user_id'], 'change_password', 'users', $_SESSION['user_id'], null, null);
                        $password_success = 'Password changed successfully!';
                        
                        // Clear any existing sessions except current one
                        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND session_token != ?");
                        $stmt->execute([$_SESSION['user_id'], $_SESSION['session_token']]);
                    } else {
                        $password_error = 'Failed to update password. Please try again.';
                    }
                }
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $password_error = 'An error occurred while changing password.';
            }
        }
    }
}

// Handle settings update submission
$settings_success = '';
$settings_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $settings_error = 'Invalid request. Please try again.';
    } else {
        // For now, just show success message
        // In a real application, you would save these preferences to database
        $settings_success = 'Settings updated successfully!';
    }
}
?>

<div class="row row-deck row-cards">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <div class="card-body">
                <?php if ($password_success): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div><?php echo htmlspecialchars($password_success); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if ($password_error): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div><?php echo htmlspecialchars($password_error); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="dashboard.php?page=settings" id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label class="form-label required">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="current_password" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <div class="form-hint">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <circle cx="12" cy="16" r="1"></circle>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            Change Password
                        </button>
                        <button type="reset" class="btn btn-secondary ms-2">Reset Form</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">Privacy & Security</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Two-Factor Authentication</label>
                    <p class="text-muted">Add an extra layer of security to your account.</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" disabled>
                        <label class="form-check-label">
                            Enable 2FA (Coming Soon)
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Session Management</label>
                    <p class="text-muted">Manage your active sessions.</p>
                    <div class="alert alert-info">
                        <strong>Current Session:</strong><br>
                        Started: <?php echo date('M j, Y g:i A', $_SESSION['login_time'] ?? time()); ?><br>
                        IP Address: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?><br>
                        Session ID: <?php echo substr(session_id(), 0, 8); ?>...
                    </div>
                    <button class="btn btn-outline-warning" onclick="terminateOtherSessions()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                        Terminate Other Sessions
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account Preferences</h3>
            </div>
            <div class="card-body">
                <?php if ($settings_success): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div><?php echo htmlspecialchars($settings_success); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" action="dashboard.php?page=settings">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="mb-3">
                        <label class="form-label">Email Notifications</label>
                        <p class="text-muted">Manage your email notification preferences.</p>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="system" checked>
                            <label class="form-check-label">
                                Receive important system notifications
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="weekly" checked>
                            <label class="form-check-label">
                                Receive weekly summary reports
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notifications[]" value="grades">
                            <label class="form-check-label">
                                Receive grade notifications (Students/Parents)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Language & Region</label>
                        <p class="text-muted">Set your preferred language and time zone.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Language</label>
                                <select class="form-select" name="language">
                                    <option value="en" selected>English</option>
                                    <option value="hi">Hindi</option>
                                    <option value="te">Telugu</option>
                                    <option value="ta">Tamil</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Time Zone</label>
                                <select class="form-select" name="timezone">
                                    <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                    <option value="America/New_York">America/New_York (EST)</option>
                                    <option value="Europe/London">Europe/London (GMT)</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dashboard Preferences</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferences[]" value="sidebar_minimized">
                            <label class="form-check-label">
                                Start with minimized sidebar
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferences[]" value="dark_mode">
                            <label class="form-check-label">
                                Enable dark mode (Coming Soon)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17,21 17,13 7,13 7,21"></polyline>
                                <polyline points="7,3 7,8 15,8"></polyline>
                            </svg>
                            Save Settings
                        </button>
                        <button type="reset" class="btn btn-secondary ms-2">Reset</button>
                    </div>
                </form>
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
                            <span class="badge bg-green">Active</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Member Since</label>
                        <div class="form-control-plaintext"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Login</label>
                        <div class="form-control-plaintext"><?php echo date('M j, Y g:i A', $_SESSION['login_time'] ?? time()); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-blue"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'super_admin'): ?>
<!-- System Settings (Admin Only) -->
<div class="row row-cards mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Settings</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">System Name</label>
                            <input type="text" class="form-control" value="School Management System" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-control" value="1.0.0" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Database Status</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-green">Connected</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">Last Backup</label>
                            <div class="form-control-plaintext">
                                <span class="text-muted">No backups configured</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h4>System Administration</h4>
                    <p>These are system-level settings. In a production environment, this would include:</p>
                    <ul class="mb-0">
                        <li>Database backup and restore functionality</li>
                        <li>Email server configuration (SMTP settings)</li>
                        <li>Security policy configuration</li>
                        <li>System maintenance and update tools</li>
                        <li>Application logs and monitoring</li>
                        <li>Performance optimization settings</li>
                    </ul>
                </div>
                
                <div class="btn-list">
                    <button class="btn btn-outline-primary" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="10,8 16,12 10,16"></polyline>
                        </svg>
                        System Backup
                    </button>
                    <button class="btn btn-outline-secondary" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                        </svg>
                        View Logs
                    </button>
                    <button class="btn btn-outline-warning" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Maintenance Mode
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle password visibility
function togglePassword(button) {
    const input = button.previousElementSibling;
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

// Form validation for password change
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = this.querySelector('input[name="new_password"]').value;
    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        showMessage('danger', 'New passwords do not match!');
        return false;
    }
    
    if (newPassword.length < 8) {
        e.preventDefault();
        showMessage('danger', 'Password must be at least 8 characters long!');
        return false;
    }
    
    // Check password strength
    if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
        if (!confirm('Password should contain at least one uppercase letter, one lowercase letter, and one number. Continue anyway?')) {
            e.preventDefault();
            return false;
        }
    }
});

// Terminate other sessions
function terminateOtherSessions() {
    if (confirm('This will log out all other devices. Continue?')) {
        // In a real implementation, this would make an AJAX call
        showMessage('info', 'Other sessions terminated successfully.');
    }
}

// Real-time password match validation
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.querySelector('input[name="new_password"]');
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    
    function validatePasswordMatch() {
        if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
            confirmPasswordInput.classList.add('is-invalid');
        } else {
            confirmPasswordInput.setCustomValidity('');
            confirmPasswordInput.classList.remove('is-invalid');
        }
    }
    
    newPasswordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
});

// Enhanced message display function
function showMessage(type, message) {
    // Remove existing messages
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
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : type === 'info' ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.auto-message)');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-dismissible')) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }
        });
    }, 5000);
});
</script>