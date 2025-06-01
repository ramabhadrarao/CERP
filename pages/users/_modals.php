<?php
// pages/users/_modals.php - Shared Modals for User Management
?>

<!-- Delete Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>Do you really want to delete user <strong id="deleteUserName"></strong>? This action cannot be undone and will remove all associated data.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, delete user</button>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Result Modal -->
<div class="modal fade" id="passwordResetModal" tabindex="-1" aria-labelledby="passwordResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordResetModalLabel">Password Reset Successful</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success mb-3">
                    <div class="d-flex">
                        <div class="me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="alert-title">New Password Generated</h4>
                            <p class="mb-0">The password has been reset successfully. Please share this new password securely with the user.</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password:</label>
                    <div class="input-group">
                        <input type="text" id="newPassword" class="form-control font-monospace" readonly value="Loading...">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()" title="Copy to clipboard">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="resetUserName" class="form-label">User:</label>
                    <div class="form-control-plaintext" id="resetUserName">Loading...</div>
                </div>
                
                <div class="alert alert-info">
                    <h5>Important Security Notice:</h5>
                    <ul class="mb-0">
                        <li>Share this password through a secure channel</li>
                        <li>The user should change this password after their first login</li>
                        <li>This password will only be shown once</li>
                        <li>All existing sessions for this user have been terminated</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyPassword()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    Copy Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading user details...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editUserFromModal">Edit User</button>
            </div>
        </div>
    </div>
</div>

<?php
// pages/users/_scripts.php - Shared JavaScript for User Management
?>

<script>
// CSRF token for AJAX requests
const csrfToken = '<?php echo generate_csrf_token(); ?>';
let currentUserId = null;

// Toggle user status via AJAX
function toggleUserStatus(userId) {
    if (confirm('Are you sure you want to change this user\'s status?')) {
        const button = document.getElementById(`toggle-btn-${userId}`);
        const statusBadge = document.getElementById(`status-${userId}`);
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        button.disabled = true;
        
        fetch(`users/ajax.php?action=toggle_status&id=${userId}&token=${encodeURIComponent(csrfToken)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error('Server returned HTML instead of JSON. Check server-side errors.');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update status badge
                statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
                statusBadge.className = `badge ${data.new_status === 'active' ? 'bg-green' : 'bg-gray'}`;
                showMessage('success', data.message);
            } else {
                showMessage('danger', data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Toggle status error:', error);
            showMessage('danger', `Error: ${error.message}`);
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Reset user password via AJAX
function resetUserPassword(userId, userName) {
    if (confirm(`Are you sure you want to reset the password for ${userName}?`)) {
        showMessage('info', 'Generating new password...');
        
        fetch(`users/ajax.php?action=reset_password&id=${userId}&token=${encodeURIComponent(csrfToken)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error('Server returned HTML instead of JSON. Check server-side errors.');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const passwordField = document.getElementById('newPassword');
                const userNameField = document.getElementById('resetUserName');
                
                if (passwordField && userNameField) {
                    passwordField.value = data.new_password;
                    userNameField.textContent = data.user_name;
                    
                    const modal = new bootstrap.Modal(document.getElementById('passwordResetModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                    showMessage('success', 'Password reset successfully!');
                } else {
                    // Fallback if modal elements don't exist
                    alert(`Password reset successful!\n\nUser: ${data.user_name}\nNew Password: ${data.new_password}\n\nPlease save this password securely.`);
                    showMessage('success', 'Password reset successfully! Password was shown in alert.');
                }
            } else {
                showMessage('danger', data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Reset password error:', error);
            showMessage('danger', `Error: ${error.message}`);
        });
    }
}

// Copy password to clipboard
function copyPassword() {
    const passwordField = document.getElementById('newPassword');
    
    if (!passwordField || !passwordField.value || passwordField.value === 'Loading...') {
        showMessage('danger', 'No password to copy.');
        return;
    }
    
    passwordField.select();
    passwordField.setSelectionRange(0, 99999);
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(passwordField.value).then(() => {
            showMessage('success', 'Password copied to clipboard!');
        }).catch(() => {
            fallbackCopy();
        });
    } else {
        fallbackCopy();
    }
    
    function fallbackCopy() {
        try {
            const success = document.execCommand('copy');
            if (success) {
                showMessage('success', 'Password copied to clipboard!');
            } else {
                showMessage('warning', 'Copy failed. Please manually select and copy the password.');
            }
        } catch (err) {
            showMessage('warning', 'Copy not supported. Please manually select and copy the password.');
        }
    }
}

// Confirm delete user
function confirmDeleteUser(userId, userName) {
    const modal = document.getElementById('deleteUserModal');
    const deleteUserName = document.getElementById('deleteUserName');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    if (deleteUserName && confirmBtn) {
        deleteUserName.textContent = userName;
        currentUserId = userId;
        
        // Remove any existing event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        // Add new event listener
        newConfirmBtn.addEventListener('click', function() {
            deleteUser(userId);
        });
        
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    } else {
        console.error('Modal elements not found');
    }
}

// Delete user via AJAX
function deleteUser(userId) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    
    // Show loading state
    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Deleting...';
    confirmBtn.disabled = true;
    
    fetch(`users/ajax.php?action=delete&id=${userId}&token=${encodeURIComponent(csrfToken)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error('Server returned HTML instead of JSON. Check server-side errors.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
            modal.hide();
            
            // Show success message
            showMessage('success', data.message);
            
            // Remove user row from table or reload page
            const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (userRow) {
                userRow.remove();
            } else {
                // Reload page if row selector not found
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showMessage('danger', data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Delete user error:', error);
        showMessage('danger', `Error: ${error.message}`);
    })
    .finally(() => {
        // Restore button state
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

// View user details
function viewUserDetails(userId) {
    const modal = document.getElementById('userDetailsModal');
    const content = document.getElementById('userDetailsContent');
    const editBtn = document.getElementById('editUserFromModal');
    
    // Show loading state
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2">Loading user details...</div>
        </div>
    `;
    
    // Set edit button action
    editBtn.onclick = () => {
        window.location.href = `dashboard.php?page=users&action=edit&id=${userId}`;
    };
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    fetch(`users/ajax.php?action=get_user_details&id=${userId}&token=${encodeURIComponent(csrfToken)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.user;
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="form-control-plaintext">${user.first_name} ${user.last_name}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <div class="form-control-plaintext">${user.username}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="form-control-plaintext">${user.email}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <div class="form-control-plaintext">
                                <span class="badge bg-purple-lt">${user.role_name || 'Unknown'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-control-plaintext">
                                <span class="badge ${user.status === 'active' ? 'bg-green' : 'bg-gray'}">${user.status}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Created</label>
                            <div class="form-control-plaintext">${new Date(user.created_at).toLocaleDateString()}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Login</label>
                            <div class="form-control-plaintext">${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Active Sessions</label>
                            <div class="form-control-plaintext">
                                <span class="badge ${user.active_sessions > 0 ? 'bg-green' : 'bg-gray'}">${user.active_sessions} active</span>
                            </div>
                        </div>
                    </div>
                </div>
                ${user.phone ? `
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <div class="form-control-plaintext">${user.phone}</div>
                        </div>
                    </div>
                </div>
                ` : ''}
                ${user.address ? `
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <div class="form-control-plaintext">${user.address}</div>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Get user details error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <strong>Error:</strong> Failed to load user details.
            </div>
        `;
    });
}

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
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap modals with proper z-index
    const modalElements = document.querySelectorAll('.modal');
    modalElements.forEach(modalEl => {
        modalEl.addEventListener('show.bs.modal', function() {
            // Remove any existing modal backdrops
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => backdrop.remove());
            
            // Set high z-index for this modal
            this.style.zIndex = '10050';
        });
        
        modalEl.addEventListener('shown.bs.modal', function() {
            // Ensure backdrop has lower z-index than modal
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '10040';
            }
        });
    });
    
    // Auto-hide alerts
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
    
    // Add data attributes to table rows for easier manipulation
    const userRows = document.querySelectorAll('tbody tr');
    userRows.forEach(row => {
        const actionButtons = row.querySelector('.dropdown-menu');
        if (actionButtons) {
            const editLink = actionButtons.querySelector('a[href*="action=edit"]');
            if (editLink) {
                const href = editLink.getAttribute('href');
                const match = href.match(/id=(\d+)/);
                if (match) {
                    row.setAttribute('data-user-id', match[1]);
                }
            }
        }
    });
});

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    const url = new URL(window.location);
    url.searchParams.delete('success');
    url.searchParams.delete('error');
    window.history.replaceState(null, null, url);
}
</script>