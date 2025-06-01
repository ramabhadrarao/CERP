<?php
// pages/blood-groups.php - Blood Groups Management CRUD
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to manage blood group records.</p>
          </div>';
    return;
}

$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$bg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_blood_group($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=blood-groups&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_blood_group($bg_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=blood-groups&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_blood_group($bg_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=blood-groups&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=blood-groups&error=' . urlencode($result['message']));
                    exit;
                }
                break;
        }
    }
}

// Get success/error messages from URL
if (isset($_GET['success'])) {
    $message = sanitize_input($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = sanitize_input($_GET['error']);
}

// CRUD Functions
function get_all_blood_groups() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT bg.*, 
                   COUNT(s.id) as student_count,
                   COUNT(f.id) as faculty_count
            FROM blood_groups bg
            LEFT JOIN students s ON bg.id = s.blood_group_id
            LEFT JOIN faculty f ON bg.id = f.blood_group_id
            GROUP BY bg.id
            ORDER BY bg.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get blood groups error: " . $e->getMessage());
        return [];
    }
}

function get_blood_group_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM blood_groups WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get blood group error: " . $e->getMessage());
        return false;
    }
}

function handle_add_blood_group($data) {
    global $pdo;
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 1) {
        $errors[] = "Blood group name is required.";
    }
    
    // Validate blood group format (A+, A-, B+, B-, AB+, AB-, O+, O-)
    if (!preg_match('/^(A|B|AB|O)[+-]$/', $data['name'])) {
        $errors[] = "Invalid blood group format. Use: A+, A-, B+, B-, AB+, AB-, O+, O-";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate
        $stmt = $pdo->prepare("SELECT id FROM blood_groups WHERE name = ?");
        $stmt->execute([$data['name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Blood group already exists.'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO blood_groups (name) VALUES (?)");
        if ($stmt->execute([$data['name']])) {
            log_audit($_SESSION['user_id'], 'create', 'blood_groups', $pdo->lastInsertId(), null, $data);
            return ['success' => true, 'message' => 'Blood group added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to add blood group.'];
        }
    } catch (Exception $e) {
        error_log("Add blood group error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_edit_blood_group($id, $data) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid blood group ID.'];
    
    $old_record = get_blood_group_by_id($id);
    if (!$old_record) return ['success' => false, 'message' => 'Blood group not found.'];
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 1) {
        $errors[] = "Blood group name is required.";
    }
    
    if (!preg_match('/^(A|B|AB|O)[+-]$/', $data['name'])) {
        $errors[] = "Invalid blood group format. Use: A+, A-, B+, B-, AB+, AB-, O+, O-";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate (excluding current record)
        $stmt = $pdo->prepare("SELECT id FROM blood_groups WHERE name = ? AND id != ?");
        $stmt->execute([$data['name'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Blood group name already exists.'];
        }
        
        $stmt = $pdo->prepare("UPDATE blood_groups SET name = ? WHERE id = ?");
        if ($stmt->execute([$data['name'], $id])) {
            log_audit($_SESSION['user_id'], 'update', 'blood_groups', $id, $old_record, $data);
            return ['success' => true, 'message' => 'Blood group updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update blood group.'];
        }
    } catch (Exception $e) {
        error_log("Edit blood group error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_delete_blood_group($id) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid blood group ID.'];
    
    try {
        $old_record = get_blood_group_by_id($id);
        if (!$old_record) return ['success' => false, 'message' => 'Blood group not found.'];
        
        // Check if blood group is in use
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM students WHERE blood_group_id = ?) +
                (SELECT COUNT(*) FROM faculty WHERE blood_group_id = ?) as usage_count
        ");
        $stmt->execute([$id, $id]);
        $result = $stmt->fetch();
        
        if ($result['usage_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete blood group: It is being used by ' . $result['usage_count'] . ' record(s).'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM blood_groups WHERE id = ?");
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            log_audit($_SESSION['user_id'], 'delete', 'blood_groups', $id, $old_record, null);
            return ['success' => true, 'message' => 'Blood group deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete blood group.'];
        }
    } catch (Exception $e) {
        error_log("Delete blood group error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get data based on action
$blood_groups = [];
$edit_blood_group = null;

if ($action === 'list' || $action === 'delete') {
    $blood_groups = get_all_blood_groups();
}

if ($action === 'edit' && $bg_id) {
    $edit_blood_group = get_blood_group_by_id($bg_id);
    if (!$edit_blood_group) {
        $error = 'Blood group not found.';
        $action = 'list';
        $blood_groups = get_all_blood_groups();
    }
}

// Define blood group colors for visual display
function get_blood_group_color($name) {
    $colors = [
        'A+' => 'bg-red', 'A-' => 'bg-red-lt',
        'B+' => 'bg-blue', 'B-' => 'bg-blue-lt',
        'AB+' => 'bg-purple', 'AB-' => 'bg-purple-lt',
        'O+' => 'bg-green', 'O-' => 'bg-green-lt'
    ];
    return $colors[$name] ?? 'bg-gray';
}
?>

<!-- Messages -->
<?php if ($message): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $message; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo $error; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
<!-- Add Blood Group Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Blood Group</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=blood-groups" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=blood-groups&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">Blood Group</label>
                <select name="name" class="form-select" required>
                    <option value="">Select Blood Group</option>
                    <option value="A+" <?php echo (($_POST['name'] ?? '') === 'A+') ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo (($_POST['name'] ?? '') === 'A-') ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo (($_POST['name'] ?? '') === 'B+') ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo (($_POST['name'] ?? '') === 'B-') ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo (($_POST['name'] ?? '') === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo (($_POST['name'] ?? '') === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo (($_POST['name'] ?? '') === 'O+') ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo (($_POST['name'] ?? '') === 'O-') ? 'selected' : ''; ?>>O-</option>
                </select>
                <div class="form-hint">Select a valid ABO blood group type.</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Add Blood Group
                </button>
                <a href="dashboard.php?page=blood-groups" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_blood_group): ?>
<!-- Edit Blood Group Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Blood Group: <?php echo htmlspecialchars($edit_blood_group['name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=blood-groups" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=blood-groups&action=edit&id=<?php echo $edit_blood_group['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">Blood Group</label>
                <select name="name" class="form-select" required>
                    <option value="">Select Blood Group</option>
                    <option value="A+" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'A+') ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'A-') ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'B+') ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'B-') ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'O+') ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo (($_POST['name'] ?? $edit_blood_group['name']) === 'O-') ? 'selected' : ''; ?>>O-</option>
                </select>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Blood Group
                </button>
                <a href="dashboard.php?page=blood-groups" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Blood Groups List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Blood Groups Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=blood-groups&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Blood Group
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Blood Group</th>
                    <th>Students Using</th>
                    <th>Faculty Using</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($blood_groups)): ?>
                    <?php foreach ($blood_groups as $bg): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar <?php echo get_blood_group_color($bg['name']); ?> text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2c-4.97 0-9 4.03-9 9 0 4.17 2.84 7.67 6.69 8.69L12 22l2.31-2.31C18.16 18.67 21 15.17 21 11c0-4.97-4.03-9-9-9z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div>
                                    <div class="font-weight-medium"><?php echo htmlspecialchars($bg['name']); ?></div>
                                    <div class="text-muted small">ID: <?php echo $bg['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($bg['student_count'] > 0): ?>
                                <span class="badge bg-green"><?php echo $bg['student_count']; ?> students</span>
                            <?php else: ?>
                                <span class="text-muted">No students</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($bg['faculty_count'] > 0): ?>
                                <span class="badge bg-blue"><?php echo $bg['faculty_count']; ?> faculty</span>
                            <?php else: ?>
                                <span class="text-muted">No faculty</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($bg['date_created'])); ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=blood-groups&action=edit&id=<?php echo $bg['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <?php if ($bg['student_count'] == 0 && $bg['faculty_count'] == 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteBloodGroup(<?php echo $bg['id']; ?>, '<?php echo htmlspecialchars($bg['name']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                                <?php else: ?>
                                <span class="badge bg-red-lt">In Use</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No blood groups found. <a href="dashboard.php?page=blood-groups&action=add">Add the first blood group</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Blood Group Distribution Chart -->
<div class="row row-cards mt-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Blood Group Distribution</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg_type): ?>
                        <?php 
                        $bg_data = array_filter($blood_groups, fn($bg) => $bg['name'] === $bg_type);
                        $bg_data = reset($bg_data);
                        $total_usage = $bg_data ? ($bg_data['student_count'] + $bg_data['faculty_count']) : 0;
                        ?>
                        <div class="col-6 col-sm-4 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body text-center">
                                    <div class="text-h1 <?php echo get_blood_group_color($bg_type); ?> text-white rounded p-2 mb-2">
                                        <?php echo $bg_type; ?>
                                    </div>
                                    <div class="text-muted"><?php echo $total_usage; ?> users</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Blood Groups</div>
                </div>
                <div class="h1 mb-3"><?php echo count($blood_groups); ?></div>
                <div class="d-flex mb-2">
                    <div>Available types</div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">In Use</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($blood_groups, fn($bg) => $bg['student_count'] > 0 || $bg['faculty_count'] > 0)); ?></div>
                <div class="d-flex mb-2">
                    <div>Being used</div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDeleteBloodGroup(bgId, bgName) {
    createTablerPopup('Confirm Deletion', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Delete Blood Group?</h3>
            <p class="text-muted">Are you sure you want to delete blood group "${bgName}"?<br>This action cannot be undone.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="deleteBloodGroup(${bgId})">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function deleteBloodGroup(bgId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `dashboard.php?page=blood-groups&action=delete&id=${bgId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo generate_csrf_token(); ?>';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}
</script>