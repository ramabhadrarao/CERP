<?php
// pages/nationality.php - Nationality Management CRUD
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to manage nationality records.</p>
          </div>';
    return;
}

$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$nationality_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_nationality($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=nationality&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_nationality($nationality_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=nationality&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_nationality($nationality_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=nationality&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=nationality&error=' . urlencode($result['message']));
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
function get_all_nationalities() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, 
                   COUNT(DISTINCT s.id) as student_count,
                   COUNT(DISTINCT fad.faculty_id) as faculty_count
            FROM nationality n
            LEFT JOIN students s ON n.id = s.nationality_id
            LEFT JOIN faculty_additional_details fad ON n.id = fad.nationality_id
            GROUP BY n.id
            ORDER BY n.name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get nationalities error: " . $e->getMessage());
        return [];
    }
}

function get_nationality_by_id($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM nationality WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get nationality error: " . $e->getMessage());
        return false;
    }
}

function handle_add_nationality($data) {
    global $pdo;
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Nationality name must be at least 2 characters long.";
    }
    
    if (!empty($data['country_code']) && strlen($data['country_code']) > 5) {
        $errors[] = "Country code must be 5 characters or less.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate
        $stmt = $pdo->prepare("SELECT id FROM nationality WHERE name = ?");
        $stmt->execute([$data['name']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Nationality already exists.'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO nationality (name, country_code) VALUES (?, ?)");
        if ($stmt->execute([$data['name'], $data['country_code'] ?: null])) {
            log_audit($_SESSION['user_id'], 'create', 'nationality', $pdo->lastInsertId(), null, $data);
            return ['success' => true, 'message' => 'Nationality added successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to add nationality.'];
        }
    } catch (Exception $e) {
        error_log("Add nationality error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_edit_nationality($id, $data) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid nationality ID.'];
    
    $old_record = get_nationality_by_id($id);
    if (!$old_record) return ['success' => false, 'message' => 'Nationality not found.'];
    
    $errors = [];
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors[] = "Nationality name must be at least 2 characters long.";
    }
    
    if (!empty($data['country_code']) && strlen($data['country_code']) > 5) {
        $errors[] = "Country code must be 5 characters or less.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    try {
        // Check for duplicate (excluding current record)
        $stmt = $pdo->prepare("SELECT id FROM nationality WHERE name = ? AND id != ?");
        $stmt->execute([$data['name'], $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Nationality name already exists.'];
        }
        
        $stmt = $pdo->prepare("UPDATE nationality SET name = ?, country_code = ? WHERE id = ?");
        if ($stmt->execute([$data['name'], $data['country_code'] ?: null, $id])) {
            log_audit($_SESSION['user_id'], 'update', 'nationality', $id, $old_record, $data);
            return ['success' => true, 'message' => 'Nationality updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update nationality.'];
        }
    } catch (Exception $e) {
        error_log("Edit nationality error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

function handle_delete_nationality($id) {
    global $pdo;
    
    if (!$id) return ['success' => false, 'message' => 'Invalid nationality ID.'];
    
    try {
        $old_record = get_nationality_by_id($id);
        if (!$old_record) return ['success' => false, 'message' => 'Nationality not found.'];
        
        // Check if nationality is in use
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM students WHERE nationality_id = ?) +
                (SELECT COUNT(*) FROM faculty_additional_details WHERE nationality_id = ?) as usage_count
        ");
        $stmt->execute([$id, $id]);
        $result = $stmt->fetch();
        
        if ($result['usage_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete nationality: It is being used by ' . $result['usage_count'] . ' record(s).'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM nationality WHERE id = ?");
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            log_audit($_SESSION['user_id'], 'delete', 'nationality', $id, $old_record, null);
            return ['success' => true, 'message' => 'Nationality deleted successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete nationality.'];
        }
    } catch (Exception $e) {
        error_log("Delete nationality error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred.'];
    }
}

// Get data based on action
$nationalities = [];
$edit_nationality = null;

if ($action === 'list' || $action === 'delete') {
    $nationalities = get_all_nationalities();
}

if ($action === 'edit' && $nationality_id) {
    $edit_nationality = get_nationality_by_id($nationality_id);
    if (!$edit_nationality) {
        $error = 'Nationality not found.';
        $action = 'list';
        $nationalities = get_all_nationalities();
    }
}

// Common country codes for quick selection
$common_countries = [
    'IN' => 'India',
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'JP' => 'Japan',
    'CN' => 'China',
    'BR' => 'Brazil'
];

function get_flag_emoji($country_code) {
    $flags = [
        'IN' => '🇮🇳', 'US' => '🇺🇸', 'GB' => '🇬🇧', 'CA' => '🇨🇦',
        'AU' => '🇦🇺', 'DE' => '🇩🇪', 'FR' => '🇫🇷', 'JP' => '🇯🇵',
        'CN' => '🇨🇳', 'BR' => '🇧🇷', 'RU' => '🇷🇺', 'IT' => '🇮🇹',
        'ES' => '🇪🇸', 'MX' => '🇲🇽', 'SA' => '🇸🇦', 'AE' => '🇦🇪'
    ];
    return $flags[$country_code] ?? '🌍';
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
<!-- Add Nationality Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Nationality</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=nationality" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=nationality&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label required">Nationality Name</label>
                        <input type="text" name="name" class="form-control" required 
                               placeholder="e.g., Indian, American, British"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        <div class="form-hint">Enter the nationality name.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Country Code</label>
                        <input type="text" name="country_code" class="form-control" 
                               placeholder="e.g., IN, US, GB" maxlength="5"
                               value="<?php echo htmlspecialchars($_POST['country_code'] ?? ''); ?>">
                        <div class="form-hint">Optional 2-5 character code.</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Quick Select Common Countries</label>
                <div class="row g-2">
                    <?php foreach ($common_countries as $code => $name): ?>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectCountry('<?php echo $name; ?>', '<?php echo $code; ?>')">
                            <?php echo get_flag_emoji($code); ?> <?php echo $name; ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Add Nationality
                </button>
                <a href="dashboard.php?page=nationality" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_nationality): ?>
<!-- Edit Nationality Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Nationality: <?php echo htmlspecialchars($edit_nationality['name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=nationality" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=nationality&action=edit&id=<?php echo $edit_nationality['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label required">Nationality Name</label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $edit_nationality['name']); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Country Code</label>
                        <input type="text" name="country_code" class="form-control" maxlength="5"
                               value="<?php echo htmlspecialchars($_POST['country_code'] ?? $edit_nationality['country_code']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Quick Select Common Countries</label>
                <div class="row g-2">
                    <?php foreach ($common_countries as $code => $name): ?>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectCountry('<?php echo $name; ?>', '<?php echo $code; ?>')">
                            <?php echo get_flag_emoji($code); ?> <?php echo $name; ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Nationality
                </button>
                <a href="dashboard.php?page=nationality" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Nationality List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Nationality Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=nationality&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Nationality
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nationality</th>
                    <th>Country Code</th>
                    <th>Students Using</th>
                    <th>Faculty Using</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($nationalities)): ?>
                    <?php foreach ($nationalities as $nationality): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="avatar bg-blue text-white">
                                        <?php 
                                        if ($nationality['country_code']) {
                                            echo get_flag_emoji($nationality['country_code']);
                                        } else {
                                            echo strtoupper(substr($nationality['name'], 0, 2));
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="font-weight-medium"><?php echo htmlspecialchars($nationality['name']); ?></div>
                                    <div class="text-muted small">ID: <?php echo $nationality['id']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($nationality['country_code']): ?>
                                <span class="badge bg-blue-lt"><?php echo htmlspecialchars($nationality['country_code']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($nationality['student_count'] > 0): ?>
                                <span class="badge bg-green"><?php echo $nationality['student_count']; ?> students</span>
                            <?php else: ?>
                                <span class="text-muted">No students</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($nationality['faculty_count'] > 0): ?>
                                <span class="badge bg-blue"><?php echo $nationality['faculty_count']; ?> faculty</span>
                            <?php else: ?>
                                <span class="text-muted">No faculty</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($nationality['date_created'])); ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=nationality&action=edit&id=<?php echo $nationality['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <?php if ($nationality['student_count'] == 0 && $nationality['faculty_count'] == 0): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteNationality(<?php echo $nationality['id']; ?>, '<?php echo htmlspecialchars($nationality['name']); ?>')">
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
                        <td colspan="6" class="text-center text-muted py-4">
                            No nationalities found. <a href="dashboard.php?page=nationality&action=add">Add the first nationality</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Nationalities</div>
                </div>
                <div class="h1 mb-3"><?php echo count($nationalities); ?></div>
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
                <div class="h1 mb-3"><?php echo count(array_filter($nationalities, fn($n) => $n['student_count'] > 0 || $n['faculty_count'] > 0)); ?></div>
                <div class="d-flex mb-2">
                    <div>Being used</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">With Country Code</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($nationalities, fn($n) => !empty($n['country_code']))); ?></div>
                <div class="d-flex mb-2">
                    <div>Have codes</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Most Used</div>
                </div>
                <?php 
                $most_used = array_reduce($nationalities, function($carry, $item) {
                    $total = $item['student_count'] + $item['faculty_count'];
                    return ($total > ($carry['total'] ?? 0)) ? ['name' => $item['name'], 'total' => $total] : $carry;
                }, ['total' => 0]);
                ?>
                <div class="h1 mb-3"><?php echo $most_used['total'] ?? 0; ?></div>
                <div class="d-flex mb-2">
                    <div><?php echo htmlspecialchars($most_used['name'] ?? 'None'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function selectCountry(name, code) {
    document.querySelector('input[name="name"]').value = name;
    document.querySelector('input[name="country_code"]').value = code;
}

function confirmDeleteNationality(nationalityId, nationalityName) {
    createTablerPopup('Confirm Deletion', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Delete Nationality?</h3>
            <p class="text-muted">Are you sure you want to delete nationality "${nationalityName}"?<br>This action cannot be undone.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="deleteNationality(${nationalityId})">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function deleteNationality(nationalityId) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `dashboard.php?page=nationality&action=delete&id=${nationalityId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo generate_csrf_token(); ?>';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}
</script>