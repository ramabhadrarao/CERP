<?php
// pages/users/index.php - Main Users List Page

// Check permissions
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    echo '<div class="alert alert-danger">
            <h4>Access Denied</h4>
            <p>You do not have permission to access user management.</p>
          </div>';
    return;
}

// Get pagination and filtering parameters
$records_per_page = 25;
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? (int)$_GET['role'] : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Get success/error messages
$message = isset($_GET['success']) ? sanitize_input($_GET['success']) : '';
$error = isset($_GET['error']) ? sanitize_input($_GET['error']) : '';

// Get users and roles data
$users = get_users_with_filters($search, $role_filter, $status_filter, $records_per_page, $offset);
$total_users = count_users_with_filters($search, $role_filter, $status_filter);
$total_pages = ceil($total_users / $records_per_page);
$roles = get_all_active_roles();

// Helper functions
function get_users_with_filters($search, $role_filter, $status_filter, $limit, $offset) {
    $pdo = get_database_connection();
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($role_filter)) {
        $where_conditions[] = "u.role_id = :role_filter";
        $params['role_filter'] = $role_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = :status_filter";
        $params['status_filter'] = $status_filter;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "
            SELECT u.*, r.name as role_name, r.description as role_description,
                   COUNT(CASE WHEN us.expires_at > NOW() THEN 1 END) as active_sessions,
                   u.created_at, u.last_login
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            LEFT JOIN user_sessions us ON u.id = us.user_id
            {$where_clause}
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        return [];
    }
}

function count_users_with_filters($search, $role_filter, $status_filter) {
    $pdo = get_database_connection();
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.username LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($role_filter)) {
        $where_conditions[] = "u.role_id = :role_filter";
        $params['role_filter'] = $role_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = :status_filter";
        $params['status_filter'] = $status_filter;
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    try {
        $sql = "SELECT COUNT(*) as total FROM users u LEFT JOIN roles r ON u.role_id = r.id {$where_clause}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    } catch (Exception $e) {
        error_log("Count users error: " . $e->getMessage());
        return 0;
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

<!-- Messages -->
<?php if ($message): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <div class="d-flex">
        <div class="me-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
        </div>
        <div><?php echo htmlspecialchars($message); ?></div>
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
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">User Management</h3>
        <div class="card-actions">
            <div class="btn-group">
                <a href="dashboard.php?page=users&action=add" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Add User
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="dashboard.php?page=users&action=bulk_import">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14,2 14,8 20,8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        Bulk Import
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="dashboard.php?page=users&action=export">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7,10 12,15 17,10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Export Users
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card-body border-bottom">
        <form method="GET" action="dashboard.php" class="row g-2">
            <input type="hidden" name="page" value="users">
            
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="role" class="form-select" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo $role_filter == $role['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role['name']))); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <?php if ($search || $role_filter || $status_filter): ?>
                <a href="dashboard.php?page=users" class="btn btn-outline-secondary w-100">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Sessions</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex py-1 align-items-center">
                                <div class="user-avatar me-3">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill">
                                    <div class="font-weight-medium">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-blue-lt ms-1">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted">
                                        <?php echo htmlspecialchars($user['username']); ?> • 
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-purple-lt">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'Unknown'))); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_class = match($user['status']) {
                                'active' => 'bg-green',
                                'inactive' => 'bg-gray',
                                'suspended' => 'bg-red',
                                'pending' => 'bg-yellow',
                                default => 'bg-gray'
                            };
                            ?>
                            <span class="badge <?php echo $status_class; ?>" id="status-<?php echo $user['id']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td class="text-muted">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($user['last_login']): ?>
                                <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted">
                            <?php if ($user['active_sessions'] > 0): ?>
                                <span class="badge bg-green"><?php echo $user['active_sessions']; ?> active</span>
                            <?php else: ?>
                                <span class="text-muted">No sessions</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-warning" onclick="toggleUserStatus(<?php echo $user['id']; ?>)" id="toggle-btn-<?php echo $user['id']; ?>" title="Toggle Status">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="dashboard.php?page=users&action=edit&id=<?php echo $user['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                            Edit User
                                        </a>
                                        
                                        <a class="dropdown-item" href="dashboard.php?page=users&action=view&id=<?php echo $user['id']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            View Details
                                        </a>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="dropdown-item" onclick="resetUserPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <circle cx="12" cy="16" r="1"></circle>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                            Reset Password
                                        </button>
                                        
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item text-danger" onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                            </svg>
                                            Delete User
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <?php if ($search || $role_filter || $status_filter): ?>
                                No users found matching your criteria. <a href="dashboard.php?page=users">Clear filters</a> to see all users.
                            <?php else: ?>
                                No users found. <a href="dashboard.php?page=users&action=add">Add the first user</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-muted">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_users); ?> 
            of <?php echo $total_users; ?> entries
        </p>
        <ul class="pagination m-0 ms-auto">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&page_num=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    prev
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=users&page_num=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=users&page_num=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $role_filter ? '&role=' . $role_filter : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                    next
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9,18 15,12 9,6"></polyline>
                    </svg>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- User Statistics -->
<div class="row row-cards mt-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Users</div>
                </div>
                <div class="h1 mb-3"><?php echo number_format($total_users); ?></div>
                <div class="d-flex mb-2">
                    <div>All registered users</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Users</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => $u['status'] === 'active')); ?></div>
                <div class="d-flex mb-2">
                    <div>Status: Active</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Online Users</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => $u['active_sessions'] > 0)); ?></div>
                <div class="d-flex mb-2">
                    <div>Currently logged in</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Administrators</div>
                </div>
                <div class="h1 mb-3"><?php echo count(array_filter($users, fn($u) => in_array($u['role_name'], ['super_admin', 'admin']))); ?></div>
                <div class="d-flex mb-2">
                    <div>Admin users</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include modals and scripts -->
<?php include 'users/_modals.php'; ?>
<?php include 'users/_scripts.php'; ?>