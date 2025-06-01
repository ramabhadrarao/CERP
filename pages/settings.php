<?php
// pages/settings.php - Settings page content
?>

<div class="row row-deck row-cards">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account Settings</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Change Password</label>
                    <p class="text-muted">Update your account password for better security.</p>
                    <button class="btn btn-outline-primary" onclick="showChangePasswordForm()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <circle cx="12" cy="16" r="1"></circle>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        Change Password
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Notifications</label>
                    <p class="text-muted">Manage your email notification preferences.</p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label">
                            Receive important system notifications
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label">
                            Receive weekly summary reports
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Language & Region</label>
                    <p class="text-muted">Set your preferred language and time zone.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select">
                                <option value="en" selected>English</option>
                                <option value="hi">Hindi</option>
                                <option value="es">Spanish</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select">
                                <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                                <option value="America/New_York">America/New_York (EST)</option>
                                <option value="Europe/London">Europe/London (GMT)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Privacy & Security</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Two-Factor Authentication</label>
                    <p class="text-muted">Add an extra layer of security to your account.</p>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch">
                        <label class="form-check-label">
                            Enable 2FA (Coming Soon)
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Login History</label>
                    <p class="text-muted">View your recent login activity.</p>
                    <button class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                        View Login History
                    </button>
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
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Form Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                        <div class="form-hint">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitPasswordChange()">Change Password</button>
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
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">System Name</label>
                            <input type="text" class="form-control" value="School Management System" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-control" value="1.0.0" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Database Status</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-green">Connected</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <h4>System Maintenance</h4>
                    <p>These are placeholder settings. In a production system, this would include:</p>
                    <ul>
                        <li>System configuration options</li>
                        <li>Database backup and restore</li>
                        <li>Email server settings</li>
                        <li>Security policy configuration</li>
                        <li>User role management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function showChangePasswordForm() {
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

function submitPasswordChange() {
    const form = document.getElementById('changePasswordForm');
    const formData = new FormData(form);
    
    // Basic validation
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match!');
        return;
    }
    
    if (newPassword.length < 8) {
        alert('Password must be at least 8 characters long!');
        return;
    }
    
    // In a real implementation, this would be an AJAX call to update the password
    alert('Password change functionality would be implemented here.');
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
    modal.hide();
    
    // Reset form
    form.reset();
}
</script>