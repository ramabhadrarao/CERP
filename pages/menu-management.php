<?php
// Menu Management Admin Interface
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin'])) {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to manage menu items.</p>
          </div>';
    return;
}

require_once 'includes/dynamic_menu.php';

$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : 'list';
$menu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($action) {
            case 'add':
                $result = handle_add_menu_item($_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=menu-management&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'edit':
                $result = handle_edit_menu_item($menu_id, $_POST);
                if ($result['success']) {
                    header('Location: dashboard.php?page=menu-management&success=' . urlencode($result['message']));
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $result = handle_delete_menu_item($menu_id);
                if ($result['success']) {
                    header('Location: dashboard.php?page=menu-management&success=' . urlencode($result['message']));
                    exit;
                } else {
                    header('Location: dashboard.php?page=menu-management&error=' . urlencode($result['message']));
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

function handle_add_menu_item($data) {
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = "Menu name is required.";
    }
    
    if (empty($data['url']) && empty($data['parent_id'])) {
        $errors[] = "URL is required for non-parent menu items.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    $menu_data = [
        'name' => $data['name'],
        'icon' => $data['icon'] ?? '',
        'url' => $data['url'] ?? '#',
        'parent_id' => $data['parent_id'] ?: null,
        'sort_order' => (int)($data['sort_order'] ?? 0),
        'required_permissions' => explode(',', $data['required_permissions'] ?? ''),
        'required_roles' => explode(',', $data['required_roles'] ?? ''),
        'menu_type' => $data['menu_type'] ?? 'sidebar',
        'css_class' => $data['css_class'] ?? '',
        'target' => $data['target'] ?? '_self',
        'description' => $data['description'] ?? ''
    ];
    
    if (add_menu_item($menu_data)) {
        log_audit($_SESSION['user_id'], 'create', 'menu_items', null, null, $menu_data);
        return ['success' => true, 'message' => 'Menu item added successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to add menu item.'];
    }
}

function handle_edit_menu_item($id, $data) {
    if (!$id) return ['success' => false, 'message' => 'Invalid menu item ID.'];
    
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = "Menu name is required.";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    $menu_data = [
        'name' => $data['name'],
        'icon' => $data['icon'] ?? '',
        'url' => $data['url'] ?? '#',
        'parent_id' => $data['parent_id'] ?: null,
        'sort_order' => (int)($data['sort_order'] ?? 0),
        'required_permissions' => explode(',', $data['required_permissions'] ?? ''),
        'required_roles' => explode(',', $data['required_roles'] ?? ''),
        'menu_type' => $data['menu_type'] ?? 'sidebar',
        'css_class' => $data['css_class'] ?? '',
        'target' => $data['target'] ?? '_self',
        'description' => $data['description'] ?? '',
        'is_active' => isset($data['is_active']) ? 1 : 0
    ];
    
    if (update_menu_item($id, $menu_data)) {
        log_audit($_SESSION['user_id'], 'update', 'menu_items', $id, null, $menu_data);
        return ['success' => true, 'message' => 'Menu item updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update menu item.'];
    }
}

function handle_delete_menu_item($id) {
    if (!$id) return ['success' => false, 'message' => 'Invalid menu item ID.'];
    
    if (delete_menu_item($id)) {
        log_audit($_SESSION['user_id'], 'delete', 'menu_items', $id, null, null);
        return ['success' => true, 'message' => 'Menu item deleted successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete menu item.'];
    }
}

$menu_items = get_all_menu_items();
$edit_menu = null;

if ($action === 'edit' && $menu_id) {
    $edit_menu = array_filter($menu_items, fn($item) => $item['id'] == $menu_id)[0] ?? null;
    if (!$edit_menu) {
        $error = 'Menu item not found.';
        $action = 'list';
    }
}
?>

<!-- Messages -->
<?php if ($message): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div><?php echo $message; ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div><?php echo $error; ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action === 'add'): ?>
<!-- Add Menu Item Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Menu Item</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=menu-management" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=menu-management&action=add">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Menu Name</label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" name="icon" class="form-control" 
                               placeholder="e.g., user, settings, home"
                               value="<?php echo htmlspecialchars($_POST['icon'] ?? ''); ?>">
                        <div class="form-hint">Icon name from Tabler Icons</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" class="form-control" 
                               placeholder="dashboard.php?page=example"
                               value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>">
                        <div class="form-hint">Leave empty for dropdown parents</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Parent Menu</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Top Level Menu</option>
                            <?php foreach ($menu_items as $item): ?>
                                <?php if ($item['parent_id'] === null): ?>
                                <option value="<?php echo $item['id']; ?>" 
                                        <?php echo (($_POST['parent_id'] ?? '') == $item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['sort_order'] ?? '0'); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="target" class="form-select">
                            <option value="_self" <?php echo (($_POST['target'] ?? '_self') === '_self') ? 'selected' : ''; ?>>Same Window</option>
                            <option value="_blank" <?php echo (($_POST['target'] ?? '') === '_blank') ? 'selected' : ''; ?>>New Window</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Required Permissions</label>
                <input type="text" name="required_permissions" class="form-control" 
                       placeholder="all, manage_users, view_students (comma separated)"
                       value="<?php echo htmlspecialchars($_POST['required_permissions'] ?? ''); ?>">
                <div class="form-hint">Comma separated list of permissions</div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Required Roles</label>
                <input type="text" name="required_roles" class="form-control" 
                       placeholder="super_admin, admin, faculty (comma separated)"
                       value="<?php echo htmlspecialchars($_POST['required_roles'] ?? ''); ?>">
                <div class="form-hint">Comma separated list of roles</div>
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Add Menu Item
                </button>
                <a href="dashboard.php?page=menu-management" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'edit' && $edit_menu): ?>
<!-- Edit Menu Item Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Menu Item: <?php echo htmlspecialchars($edit_menu['name']); ?></h3>
        <div class="card-actions">
            <a href="dashboard.php?page=menu-management" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15,18 9,12 15,6"></polyline>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="dashboard.php?page=menu-management&action=edit&id=<?php echo $edit_menu['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label required">Menu Name</label>
                        <input type="text" name="name" class="form-control" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $edit_menu['name']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" name="icon" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['icon'] ?? $edit_menu['icon']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="text" name="url" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['url'] ?? $edit_menu['url']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Parent Menu</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Top Level Menu</option>
                            <?php foreach ($menu_items as $item): ?>
                                <?php if ($item['parent_id'] === null && $item['id'] !== $edit_menu['id']): ?>
                                <option value="<?php echo $item['id']; ?>" 
                                        <?php echo (($_POST['parent_id'] ?? $edit_menu['parent_id']) == $item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['sort_order'] ?? $edit_menu['sort_order']); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="target" class="form-select">
                            <option value="_self" <?php echo (($_POST['target'] ?? $edit_menu['target']) === '_self') ? 'selected' : ''; ?>>Same Window</option>
                            <option value="_blank" <?php echo (($_POST['target'] ?? $edit_menu['target']) === '_blank') ? 'selected' : ''; ?>>New Window</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="is_active" 
                                   <?php echo (($_POST['is_active'] ?? $edit_menu['is_active']) ? 'checked' : ''); ?>>
                            <span class="form-check-label">Active</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Required Permissions</label>
                <?php 
                $current_permissions = json_decode($edit_menu['required_permissions'], true) ?: [];
                ?>
                <input type="text" name="required_permissions" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['required_permissions'] ?? implode(',', $current_permissions)); ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Required Roles</label>
                <?php 
                $current_roles = json_decode($edit_menu['required_roles'], true) ?: [];
                ?>
                <input type="text" name="required_roles" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['required_roles'] ?? implode(',', $current_roles)); ?>">
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Update Menu Item
                </button>
                <a href="dashboard.php?page=menu-management" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Menu Items List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Menu Management</h3>
        <div class="card-actions">
            <a href="dashboard.php?page=menu-management&action=add" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Menu Item
            </a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Menu Structure</th>
                    <th>URL</th>
                    <th>Permissions</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($menu_items)): ?>
                    <?php foreach ($menu_items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($item['parent_id']): ?>
                                    <span class="text-muted me-2">└─</span>
                                <?php endif; ?>
                                
                                <div class="me-3">
                                    <?php echo get_icon_svg($item['icon']); ?>
                                </div>
                                <div>
                                    <div class="font-weight-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <?php if ($item['parent_name']): ?>
                                        <div class="text-muted small">Under: <?php echo htmlspecialchars($item['parent_name']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($item['children_count'] > 0): ?>
                                        <div class="text-info small"><?php echo $item['children_count']; ?> children</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($item['url'] && $item['url'] !== '#'): ?>
                                <code class="small"><?php echo htmlspecialchars($item['url']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">Dropdown parent</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $permissions = json_decode($item['required_permissions'], true) ?: [];
                            if (!empty($permissions)):
                                foreach (array_slice($permissions, 0, 2) as $perm):
                            ?>
                                <span class="badge bg-blue-lt me-1"><?php echo htmlspecialchars($perm); ?></span>
                            <?php 
                                endforeach;
                                if (count($permissions) > 2):
                            ?>
                                <span class="badge bg-gray-lt">+<?php echo count($permissions) - 2; ?> more</span>
                            <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">No restrictions</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            $roles = json_decode($item['required_roles'], true) ?: [];
                            if (!empty($roles)):
                                foreach (array_slice($roles, 0, 2) as $role):
                            ?>
                                <span class="badge bg-green-lt me-1"><?php echo htmlspecialchars($role); ?></span>
                            <?php 
                                endforeach;
                                if (count($roles) > 2):
                            ?>
                                <span class="badge bg-gray-lt">+<?php echo count($roles) - 2; ?> more</span>
                            <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">All roles</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_active']): ?>
                                <span class="badge bg-green">Active</span>
                            <?php else: ?>
                                <span class="badge bg-red">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php echo $item['sort_order']; ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <a href="dashboard.php?page=menu-management&action=edit&id=<?php echo $item['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDeleteMenuItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No menu items found. <a href="dashboard.php?page=menu-management&action=add">Add the first menu item</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDeleteMenuItem(menuId, menuName) {
    createTablerPopup('Confirm Deletion', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-danger" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Delete Menu Item?</h3>
            <p class="text-muted">Are you sure you want to delete "${menuName}"?<br>This will also delete all child menu items.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="deleteMenuItem(${menuId})">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function deleteMenuItem(menuId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `dashboard.php?page=menu-management&action=delete&id=${menuId}`;
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = 'csrf_token';
    csrfToken.value = '<?php echo generate_csrf_token(); ?>';
    
    form.appendChild(csrfToken);
    document.body.appendChild(form);
    form.submit();
}
</script>