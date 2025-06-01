<?php
// pages/gender.php - Gender Management CRUD
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to manage gender records.</p>
          </div>';
    return;
}

$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$gender_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_gender($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=gender&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_gender($gender_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=gender&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_gender($gender_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=gender&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=gender&error=' . urlencode($result['message']));
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
function get_all_genders() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   COUNT(s.id) as student_count,
                   COUNT(f.id) as faculty_count
            FROM gender g
            LEFT JOIN students s ON g.id = s.gender_id
            LEFT JOIN faculty f ON g.id = f.gender_id
            GROUP BY g.id
            ORDER BY g.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get genders error: " . $e->getMessage());
        return [];
    }
}

function get_gender_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM gender WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get gender error: " . $e->getMessage());
        return false;
    }
}

function handle_add_gender($data) {
    global $pdo;
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Gender name must be at least 2 characters long.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate
        $stmt = $pdo->prepare("SELECT id FROM gender WHERE name = ?");
        $stmt->execute([$data['name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Gender already exists.'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO gender (name) VALUES (?)");
        if ($stmt->execute([$data['name']])) {
            log_audit($_SESSION['user_id'], 'create', 'gender', $pdo->lastInsertId(), null, $data);
            return ['success' => true, 'message' => 'Gender added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to add gender.'];
        }
    } catch (Exception $e) {
        error_log("Add gender error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_edit_gender($id, $data) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid gender ID.'];
    
    $old_record = get_gender_by_id($id);
    if (!$old_record) return ['success' => false, 'message' => 'Gender not found.'];
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Gender name must be at least 2 characters long.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate (excluding current record)
        $stmt = $pdo->prepare("SELECT id FROM gender WHERE name = ? AND id != ?");
        $stmt->execute([$data['name'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Gender name already exists.'];
        }
        
        $stmt = $pdo->prepare("UPDATE gender SET name = ? WHERE id = ?");
        if ($stmt->execute([$data['name'], $id])) {
            log_audit($_SESSION['user_id'], 'update', 'gender', $id, $old_record, $data);
            return ['success' => true, 'message' => 'Gender updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update gender.'];
        }
    } catch (Exception $e) {
        error_log("Edit gender error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_delete_gender($id) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid gender ID.'];
    
    try {
        $old_record = get_gender_by_id($id);
        if (!$old_record) return ['success' => false, 'message' => 'Gender not found.'];
        
        // Check if gender is in use
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM students WHERE gender_id = ?) +
                (SELECT COUNT(*) FROM faculty WHERE gender_id = ?) as usage_count
        ");
        $stmt->execute([$id, $id]);
        $result = $stmt->fetch();
        
        if ($result['usage_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete gender: It is being used by ' . $result['usage_count'] . ' record(s).'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM gender WHERE id = ?");
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            log_audit($_SESSION['user_id'], 'delete', 'gender', $id, $old_record, null);
            return ['success' => true, 'message' => 'Gender deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete gender.'];
        }
    } catch (Exception $e) {
        error_log("Delete gender error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get data based on action
$genders = [];
$edit_gender = null;

if ($action === 'list' || $action === 'delete') {
    $genders = get_all_genders();
}

if ($action === 'edit' && $gender_id) {
    $edit_gender = get_gender_by_id($gender_id);
    if (!$edit_gender) {
        $error = 'Gender not found.';
        $action = 'list';
        $genders = get_all_genders();
    }
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
<!-- Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Genders</div>
                </div>
                <div class="h1 mb-3"><?php echo count($genders); ?></div>
                <div class="d-flex mb-2">
                    <div>Available options</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">In Use</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($genders, fn($g) => $g['student_count'] > 0 || $g['faculty_count'] > 0)); ?></div>
                <div class="d-flex mb-2">
                    <div>Being used</div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if ($action === 'add'): ?>
<!-- Add Gender Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Gender</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=gender" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=gender&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">Gender Name</label>
                <input type="text" name="name" class="form-control" required 
                       placeholder="e.g., Male, Female, Other"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                <div class="form-hint">Enter a descriptive gender name.</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Add Gender
                </button>
                <a href="dashboard.php?page=gender" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_gender): ?>
<!-- Edit Gender Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Gender: <?php echo htmlspecialchars($edit_gender['name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=gender" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=gender&action=edit&id=<?php echo $edit_gender['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="mb-3">
                <label class="form-label required">Gender Name</label>
                <input type="text" name="name" class="form-control" required 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? $edit_gender['name']); ?>">
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Gender
                </button>
                <a href="dashboard.php?page=gender" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Gender List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gender Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=gender&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Gender
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Students Using</th>
                    <th>Faculty Using</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($genders)): ?>
                    <?php foreach ($genders as $gender): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-blue text-white">
                                        <?php echo strtoupper(substr($gender['name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="font-weight-medium"><?php echo htmlspecialchars($gender['name']); ?></div>
                                    <div class="text-muted small">ID: <?php echo $gender['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($gender['student_count'] > 0): ?>
                                <span class="badge bg-green"><?php echo $gender['student_count']; ?> students</span>
                            <?php else: ?>
                                <span class="text-muted">No students</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($gender['faculty_count'] > 0): ?>
                                <span class="badge bg-blue"><?php echo $gender['faculty_count']; ?> faculty</span>
                            <?php else: ?>
                                <span class="text-muted">No faculty</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($gender['date_created'])); ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=gender&action=edit&id=<?php echo $gender['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <?php if ($gender['student_count'] == 0 && $gender['faculty_count'] == 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteGender(<?php echo $gender['id']; ?>, '<?php echo htmlspecialchars($gender['name']); ?>')">
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
                            No genders found. <a href="dashboard.php?page=gender&action=add">Add the first gender</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php endif; ?>

<script>
function confirmDeleteGender(genderId, genderName) {
    createTablerPopup('Confirm Deletion', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Delete Gender?</h3>
            <p class="text-muted">Are you sure you want to delete "${genderName}"?<br>This action cannot be undone.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="deleteGender(${genderId})">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function deleteGender(genderId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `dashboard.php?page=gender&action=delete&id=${genderId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo generate_csrf_token(); ?>';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}
</script>