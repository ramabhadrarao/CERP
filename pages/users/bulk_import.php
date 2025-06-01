<?php
// pages/users/bulk_import.php - Bulk Import Users

// Check permissions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to bulk import users.</p>
          </div>';
    return;
}

// Handle form submission
$errors = [];
$success = '';
$import_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $result = handle_bulk_import($_FILES, $_POST);
        if ($result['success']) {
            $success = $result['message'];
            $import_results = $result['details'] ?? [];
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Get roles for validation
$roles = get_all_active_roles();

function handle_bulk_import($files, $data) {
    $pdo = get_database_connection();
    
    if (!isset($files['bulk_file']) || $files['bulk_file']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Please select a valid CSV file.'];
    }
    
    $file = $files['bulk_file'];
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    
    if (!in_array($file['type'], $allowed_types) && !str_ends_with($file['name'], '.csv')) {
        return ['success' => false, 'message' => 'Only CSV files are allowed.'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }
    
    try {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            throw new Exception("Could not open CSV file.");
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception("CSV file is empty or invalid.");
        }
        
        // Clean and normalize headers
        $header = array_map(function($h) {
            return strtolower(trim($h));
        }, $header);
        
        // Expected columns
        $required_columns = ['first_name', 'last_name', 'email', 'username', 'role'];
        $optional_columns = ['phone', 'address', 'status', 'password'];
        
        // Map header to indices
        $column_map = [];
        foreach ($header as $index => $column) {
            $column_map[$column] = $index;
        }
        
        // Check required columns
        foreach ($required_columns as $required) {
            if (!isset($column_map[$required])) {
                throw new Exception("Required column '{$required}' not found in CSV. Available columns: " . implode(', ', $header));
            }
        }
        
        $pdo->beginTransaction();
        
        // Get roles mapping
        $roles = [];
        $stmt = $pdo->query("SELECT id, name FROM roles WHERE status = 'active'");
        while ($role = $stmt->fetch()) {
            $roles[strtolower($role['name'])] = $role['id'];
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $successes = [];
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false && $row_number <= 1000) { // Limit to 1000 rows
            $row_number++;
            
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Extract data from row
                $user_data = [];
                foreach ($column_map as $column => $index) {
                    $user_data[$column] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                
                // Validate required fields
                foreach ($required_columns as $required) {
                    if (empty($user_data[$required])) {
                        throw new Exception("Row {$row_number}: {$required} is required.");
                    }
                }
                
                // Validate email
                if (!filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Row {$row_number}: Invalid email format '{$user_data['email']}'.");
                }
                
                // Validate username
                if (!preg_match('/^[a-zA-Z0-9._-]+$/', $user_data['username'])) {
                    throw new Exception("Row {$row_number}: Invalid username format '{$user_data['username']}'.");
                }
                
                // Get role ID
                $role_name = strtolower($user_data['role']);
                if (!isset($roles[$role_name])) {
                    throw new Exception("Row {$row_number}: Invalid role '{$user_data['role']}'. Available roles: " . implode(', ', array_keys($roles)));
                }
                $role_id = $roles[$role_name];
                
                // Check for duplicates
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$user_data['username'], $user_data['email']]);
                if ($stmt->fetch()) {
                    throw new Exception("Row {$row_number}: Username '{$user_data['username']}' or email '{$user_data['email']}' already exists.");
                }
                
                // Generate password if not provided
                $password = !empty($user_data['password']) ? $user_data['password'] : generate_secure_password(12);
                
                // Validate password
                if (strlen($password) < 8) {
                    throw new Exception("Row {$row_number}: Password must be at least 8 characters long.");
                }
                
                $user_uuid = generate_uuid();
                
                // Create user account
                $stmt = $pdo->prepare("
                    INSERT INTO users (user_uuid, username, email, password_hash, role_id, first_name, last_name, phone, address, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $user_uuid,
                    $user_data['username'],
                    $user_data['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    $role_id,
                    $user_data['first_name'],
                    $user_data['last_name'],
                    $user_data['phone'] ?? null,
                    $user_data['address'] ?? null,
                    $user_data['status'] ?? 'active'
                ]);
                
                if (!$result) {
                    throw new Exception("Row {$row_number}: Failed to create user account.");
                }
                
                $success_count++;
                $successes[] = [
                    'row' => $row_number,
                    'username' => $user_data['username'],
                    'email' => $user_data['email'],
                    'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                    'password' => empty($user_data['password']) ? $password : '(provided)',
                    'role' => $user_data['role']
                ];
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                $error_count++;
                continue;
            }
        }
        
        fclose($handle);
        
        if ($success_count > 0) {
            // Log bulk import action
            log_audit($_SESSION['user_id'], 'bulk_import_users', 'users', null, null, [
                'success_count' => $success_count,
                'error_count' => $error_count,
                'file_name' => $file['name']
            ]);
            
            $pdo->commit();
            
            $message = "Bulk import completed successfully!";
            
            return [
                'success' => true, 
                'message' => $message,
                'details' => [
                    'success_count' => $success_count,
                    'error_count' => $error_count,
                    'successes' => $successes,
                    'errors' => $errors
                ]
            ];
        } else {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'No users were imported. Errors: ' . implode('; ', array_slice($errors, 0, 5))];
        }
        
    } catch (Exception $e) {
        if (isset($handle)) fclose($handle);
        $pdo->rollBack();
        error_log("Bulk import error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
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

<!-- Success Message -->
<?php if ($success): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div>
            <?php echo htmlspecialchars($success); ?>
            <?php if (!empty($import_results)): ?>
                <br><strong><?php echo $import_results['success_count']; ?></strong> users imported successfully.
                <?php if ($import_results['error_count'] > 0): ?>
                    <br><strong><?php echo $import_results['error_count']; ?></strong> rows had errors.
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Bulk Import Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bulk Import Users</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=users" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to Users
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="downloadTemplate()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Download Template
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <h4>CSV Format Requirements</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Required Columns:</strong></p>
                    <ul>
                        <li><code>first_name</code> - User's first name</li>
                        <li><code>last_name</code> - User's last name</li>
                        <li><code>email</code> - Valid email address (must be unique)</li>
                        <li><code>username</code> - Username (must be unique, alphanumeric)</li>
                        <li><code>role</code> - User role (<?php echo implode(', ', array_column($roles, 'name')); ?>)</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <p><strong>Optional Columns:</strong></p>
                    <ul>
                        <li><code>phone</code> - Phone number</li>
                        <li><code>address</code> - Full address</li>
                        <li><code>status</code> - active, inactive, or pending (default: active)</li>
                        <li><code>password</code> - Custom password (default: auto-generated)</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3">
                <strong>Important Notes:</strong>
                <ul class="mb-0">
                    <li>Maximum file size: 5MB</li>
                    <li>Maximum rows: 1,000 per import</li>
                    <li>If password is not provided, a secure password will be generated automatically</li>
                    <li>Email addresses and usernames must be unique across the system</li>
                    <li>Role names are case-insensitive</li>
                </ul>
            </div>
        </div>
        
        <form method="POST" action="dashboard.php?page=users&action=bulk_import" enctype="multipart/form-data" id="bulkImportForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">CSV File</label>
                <input type="file" name="bulk_file" class="form-control" accept=".csv" required id="csvFile">
                <div class="form-hint">Select a CSV file containing user data. Must follow the format requirements above.</div>
                <div class="invalid-feedback">Please select a valid CSV file.</div>
            </div>
            
            <!-- File preview -->
            <div class="mb-3" id="filePreview" style="display: none;">
                <label class="form-label">File Preview</label>
                <div class="card">
                    <div class="card-body">
                        <div id="previewContent">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-2">Analyzing CSV file...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary" id="importBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    Import Users
                </button>
                <a href="dashboard.php?page=users" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- Import Results -->
<?php if (!empty($import_results) && $import_results['success_count'] > 0): ?>
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Import Results</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card bg-success-lt">
                    <div class="card-body text-center">
                        <div class="h1 text-success"><?php echo $import_results['success_count']; ?></div>
                        <div class="text-success">Successfully Imported</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger-lt">
                    <div class="card-body text-center">
                        <div class="h1 text-danger"><?php echo $import_results['error_count']; ?></div>
                        <div class="text-danger">Errors</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info-lt">
                    <div class="card-body text-center">
                        <div class="h1 text-info"><?php echo $import_results['success_count'] + $import_results['error_count']; ?></div>
                        <div class="text-info">Total Processed</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Successful Imports -->
        <?php if (!empty($import_results['successes'])): ?>
        <h4>Successfully Imported Users</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($import_results['successes'], 0, 20) as $success): ?>
                    <tr>
                        <td><?php echo $success['row']; ?></td>
                        <td><?php echo htmlspecialchars($success['name']); ?></td>
                        <td><?php echo htmlspecialchars($success['username']); ?></td>
                        <td><?php echo htmlspecialchars($success['email']); ?></td>
                        <td><span class="badge bg-green-lt"><?php echo htmlspecialchars($success['role']); ?></span></td>
                        <td>
                            <?php if ($success['password'] !== '(provided)'): ?>
                                <code class="text-success"><?php echo htmlspecialchars($success['password']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">User provided</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($import_results['successes']) > 20): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            ... and <?php echo count($import_results['successes']) - 20; ?> more users
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Errors -->
        <?php if (!empty($import_results['errors'])): ?>
        <h4 class="mt-4">Import Errors</h4>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach (array_slice($import_results['errors'], 0, 10) as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
                <?php if (count($import_results['errors']) > 10): ?>
                <li><em>... and <?php echo count($import_results['errors']) - 10; ?> more errors</em></li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <strong>Important:</strong> Please save the generated passwords in a secure location and share them with users through a secure channel.
            Users should change their passwords after their first login.
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csvFile = document.getElementById('csvFile');
    const filePreview = document.getElementById('filePreview');
    const previewContent = document.getElementById('previewContent');
    const form = document.getElementById('bulkImportForm');
    const importBtn = document.getElementById('importBtn');
    
    // File selection handler
    csvFile.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                showMessage('danger', 'File size too large. Maximum 5MB allowed.');
                this.value = '';
                return;
            }
            
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showMessage('danger', 'Please select a CSV file.');
                this.value = '';
                return;
            }
            
            previewCSV(file);
        } else {
            filePreview.style.display = 'none';
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const file = csvFile.files[0];
        if (!file) {
            e.preventDefault();
            csvFile.classList.add('is-invalid');
            showMessage('danger', 'Please select a CSV file.');
            return;
        }
        
        // Disable submit button to prevent double submission
        importBtn.disabled = true;
        importBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
            Importing...
        `;
        
        // Re-enable after 30 seconds as fallback
        setTimeout(() => {
            importBtn.disabled = false;
            importBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                Import Users
            `;
        }, 30000);
    });
    
    function previewCSV(file) {
        filePreview.style.display = 'block';
        previewContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2">Analyzing CSV file...</div>
            </div>
        `;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const csv = e.target.result;
                const lines = csv.split('\n').filter(line => line.trim());
                
                if (lines.length < 2) {
                    throw new Error('CSV file must contain at least a header row and one data row.');
                }
                
                const headers = lines[0].split(',').map(h => h.trim().toLowerCase().replace(/['"]/g, ''));
                const requiredColumns = ['first_name', 'last_name', 'email', 'username', 'role'];
                const missingColumns = requiredColumns.filter(col => !headers.includes(col));
                
                let previewHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>File Name:</strong> ${file.name}<br>
                            <strong>File Size:</strong> ${(file.size / 1024).toFixed(2)} KB<br>
                            <strong>Total Rows:</strong> ${lines.length - 1} data rows
                        </div>
                        <div class="col-md-6">
                            <strong>Columns Found:</strong> ${headers.length}<br>
                            <strong>Status:</strong> ${missingColumns.length === 0 ? '<span class="text-success">✓ Valid format</span>' : '<span class="text-danger">✗ Missing required columns</span>'}
                        </div>
                    </div>
                `;
                
                if (missingColumns.length > 0) {
                    previewHTML += `
                        <div class="alert alert-danger">
                            <strong>Missing Required Columns:</strong> ${missingColumns.join(', ')}<br>
                            <strong>Found Columns:</strong> ${headers.join(', ')}
                        </div>
                    `;
                }
                
                // Show preview of first few rows
                previewHTML += `
                    <h5>Preview (first 5 rows):</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                `;
                
                headers.forEach(header => {
                    const isRequired = requiredColumns.includes(header);
                    previewHTML += `<th class="${isRequired ? 'text-primary' : ''}">${header}${isRequired ? ' *' : ''}</th>`;
                });
                previewHTML += '</tr></thead><tbody>';
                
                // Show first 5 data rows
                for (let i = 1; i < Math.min(6, lines.length); i++) {
                    const cells = lines[i].split(',').map(cell => cell.trim().replace(/['"]/g, ''));
                    previewHTML += '<tr>';
                    headers.forEach((header, index) => {
                        const value = cells[index] || '';
                        previewHTML += `<td>${value.length > 30 ? value.substring(0, 30) + '...' : value}</td>`;
                    });
                    previewHTML += '</tr>';
                }
                
                previewHTML += '</tbody></table></div>';
                
                if (lines.length > 6) {
                    previewHTML += `<p class="text-muted">... and ${lines.length - 6} more rows</p>`;
                }
                
                previewContent.innerHTML = previewHTML;
                
            } catch (error) {
                previewContent.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error reading CSV file:</strong> ${error.message}
                    </div>
                `;
            }
        };
        
        reader.readAsText(file);
    }
});

// Download CSV template
function downloadTemplate() {
    const csvContent = [
        'first_name,last_name,email,username,role,phone,address,status,password',
        'John,Doe,john.doe@college.edu,johndoe,faculty,+1-555-1234,"123 Main St, City, State",active,',
        'Jane,Smith,jane.smith@college.edu,janesmith,student,+1-555-5678,"456 Oak Ave, City, State",active,',
        'Bob,Johnson,bob.johnson@college.edu,bobjohnson,admin,+1-555-9012,"789 Pine Rd, City, State",active,SecurePass123'
    ].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'user_import_template.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showMessage('success', 'Template downloaded successfully!');
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