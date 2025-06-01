<?php
// test-popup.php - Comprehensive Tabler UI popup testing page
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = 'test-popup';
$page_title = 'Popup Testing - Tabler UI';

// Get user info for header
$pdo = get_database_connection();
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Dummy role-specific data for testing
$role_specific_data = ['unread_notifications' => 3];

// Include header
include 'includes/header.php';
include 'includes/navmenu.php';
?>

<!-- Page Content -->
<div class="page-wrapper content-wrapper">
    <div class="page-header d-print-none">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Popup Testing - Tabler UI</h2>
                    <div class="text-muted mt-1">Test various popup styles and AJAX functionality</div>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15,18 9,12 15,6"></polyline>
                            </svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-fluid">
            <!-- Test Controls -->
            <div class="row row-cards">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Tabler Modals</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-simple">
                                    Simple Modal
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-large">
                                    Large Modal
                                </button>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modal-scrollable">
                                    Scrollable Modal
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-danger">
                                    Danger Modal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Custom Popups</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-blue" onclick="showCustomPopup()">
                                    Custom Popup
                                </button>
                                <button type="button" class="btn btn-purple" onclick="showFormPopup()">
                                    Form Popup
                                </button>
                                <button type="button" class="btn btn-green" onclick="showConfirmPopup()">
                                    Confirm Dialog
                                </button>
                                <button type="button" class="btn btn-orange" onclick="showDataPopup()">
                                    Data Display
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">AJAX Operations</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-cyan" onclick="testAjaxPopup()">
                                    AJAX Test Popup
                                </button>
                                <button type="button" class="btn btn-pink" onclick="simulateUserAction('toggle_status', 123)">
                                    Toggle Status
                                </button>
                                <button type="button" class="btn btn-lime" onclick="simulateUserAction('reset_password', 456)">
                                    Reset Password
                                </button>
                                <button type="button" class="btn btn-yellow" onclick="showLoadingPopup()">
                                    Loading Dialog
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Display -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Message Testing</h3>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <button type="button" class="btn btn-success" onclick="showMessage('success', 'Operation completed successfully!')">
                                    Success Message
                                </button>
                                <button type="button" class="btn btn-danger" onclick="showMessage('danger', 'An error occurred!')">
                                    Error Message
                                </button>
                                <button type="button" class="btn btn-warning" onclick="showMessage('warning', 'Please review your input!')">
                                    Warning Message
                                </button>
                                <button type="button" class="btn btn-info" onclick="showMessage('info', 'Information updated!')">
                                    Info Message
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Z-Index Testing -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Z-Index Hierarchy Test</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h4>Current Z-Index Hierarchy:</h4>
                                <ul class="mb-0">
                                    <li><strong>Page content:</strong> 1</li>
                                    <li><strong>Top header:</strong> 1020</li>
                                    <li><strong>Sidebar:</strong> 1030</li>
                                    <li><strong>Dropdown menus:</strong> 1050</li>
                                    <li><strong>Modal backdrop:</strong> 1055</li>
                                    <li><strong>Modals:</strong> 1060</li>
                                    <li><strong>Tooltips:</strong> 1070</li>
                                    <li><strong>Custom popups:</strong> 9999</li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="testZIndexHierarchy()">
                                Test Z-Index Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabler Modal Examples -->
<!-- Simple Modal -->
<div class="modal modal-blur fade" id="modal-simple" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Simple Modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This is a simple Tabler UI modal example. It demonstrates the basic modal structure with proper styling.</p>
                <p>The modal includes a header, body, and footer with consistent Tabler design elements.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Large Modal -->
<div class="modal modal-blur fade" id="modal-large" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Large Modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Left Column</h4>
                        <p>This is a large modal that can accommodate more content. It's useful for forms or detailed information display.</p>
                        <div class="form-group mb-3">
                            <label class="form-label">Sample Input</label>
                            <input type="text" class="form-control" placeholder="Enter text here">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>Right Column</h4>
                        <p>Large modals can contain multiple columns and complex layouts while maintaining the Tabler design consistency.</p>
                        <div class="form-group mb-3">
                            <label class="form-label">Sample Select</label>
                            <select class="form-select">
                                <option>Option 1</option>
                                <option>Option 2</option>
                                <option>Option 3</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Scrollable Modal -->
<div class="modal modal-blur fade" id="modal-scrollable" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scrollable Modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>This modal has a scrollable body when content exceeds the available height.</p>
                <?php for($i = 1; $i <= 20; $i++): ?>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Line <?php echo $i; ?>.</p>
                <?php endfor; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Danger Modal -->
<div class="modal modal-blur fade" id="modal-danger" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="modal-title">Are you sure?</div>
                <div>If you proceed, you will lose all your unsaved data.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Yes, delete it</button>
            </div>
        </div>
    </div>
</div>

<script>
// Custom Popup Functions
function showCustomPopup() {
    createTablerPopup('Custom Popup', `
        <div class="mb-3">
            <p>This is a custom popup using Tabler UI styling conventions.</p>
            <div class="progress mb-3">
                <div class="progress-bar" style="width: 65%" role="progressbar" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
                    65%
                </div>
            </div>
            <div class="btn-list">
                <button class="btn btn-success btn-sm" onclick="closeTablerPopup()">Accept</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </div>
    `);
}

function showFormPopup() {
    createTablerPopup('User Information Form', `
        <form onsubmit="handleFormSubmit(event)">
            <div class="mb-3">
                <label class="form-label required">Full Name</label>
                <input type="text" class="form-control" name="name" required placeholder="Enter your full name">
            </div>
            <div class="mb-3">
                <label class="form-label required">Email</label>
                <input type="email" class="form-control" name="email" required placeholder="Enter your email">
            </div>
            <div class="mb-3">
                <label class="form-label">Department</label>
                <select class="form-select" name="department">
                    <option value="">Select Department</option>
                    <option value="cs">Computer Science</option>
                    <option value="math">Mathematics</option>
                    <option value="physics">Physics</option>
                    <option value="chemistry">Chemistry</option>
                </select>
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="notifications">
                    <label class="form-check-label">Receive email notifications</label>
                </div>
            </div>
            <div class="btn-list">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="button" class="btn btn-secondary" onclick="closeTablerPopup()">Cancel</button>
            </div>
        </form>
    `);
}

function showConfirmPopup() {
    createTablerPopup('Confirm Action', `
        <div class="text-center">
            <div class="mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-warning" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 9v2m0 4v.01"/>
                    <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                </svg>
            </div>
            <h3>Confirm Deletion</h3>
            <p class="text-muted">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="btn-list">
                <button class="btn btn-danger" onclick="handleConfirm(true)">Yes, delete</button>
                <button class="btn btn-outline-secondary" onclick="handleConfirm(false)">Cancel</button>
            </div>
        </div>
    `);
}

function showDataPopup() {
    createTablerPopup('User Details', `
        <div class="card-table table-responsive">
            <table class="table table-vcenter">
                <tbody>
                    <tr>
                        <td class="w-1">
                            <span class="avatar" style="background-image: url(https://preview.tabler.io/static/avatars/000m.jpg)"></span>
                        </td>
                        <td>
                            <div>John Doe</div>
                            <div class="text-muted">Student ID: 2024001</div>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-green">Active</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row g-3 mt-3">
            <div class="col-6">
                <div class="subheader">Department</div>
                <div>Computer Science</div>
            </div>
            <div class="col-6">
                <div class="subheader">Year</div>
                <div>2nd Year</div>
            </div>
            <div class="col-6">
                <div class="subheader">GPA</div>
                <div class="text-green">3.8</div>
            </div>
            <div class="col-6">
                <div class="subheader">Credits</div>
                <div>120/160</div>
            </div>
        </div>
        <div class="mt-4">
            <div class="btn-list">
                <button class="btn btn-primary" onclick="closeTablerPopup()">Edit Profile</button>
                <button class="btn btn-outline-secondary" onclick="closeTablerPopup()">Close</button>
            </div>
        </div>
    `);
}

function testAjaxPopup() {
    // Show loading state
    createTablerPopup('AJAX Test', `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Testing AJAX functionality...</p>
        </div>
    `);

    // Simulate AJAX call
    fetch('ajax_debug.php?action=test_popup')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTablerPopup('AJAX Test Results', `
                    <div class="alert alert-success">
                        <div class="d-flex">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">Success!</h4>
                                <div class="text-muted">${data.message}</div>
                            </div>
                        </div>
                    </div>
                    ${data.popup_html || ''}
                    <div class="btn-list mt-3">
                        <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
                    </div>
                `);
            } else {
                updateTablerPopup('AJAX Error', `
                    <div class="alert alert-danger">
                        <h4>Error</h4>
                        <p>${data.message || 'Unknown error occurred'}</p>
                    </div>
                    <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
                `);
            }
        })
        .catch(error => {
            updateTablerPopup('AJAX Error', `
                <div class="alert alert-danger">
                    <h4>Network Error</h4>
                    <p>Failed to connect to server: ${error.message}</p>
                </div>
                <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
            `);
        });
}

function simulateUserAction(action, userId) {
    const actionLabels = {
        'toggle_status': 'Toggle User Status',
        'reset_password': 'Reset Password'
    };

    createTablerPopup(actionLabels[action] || 'User Action', `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <p>Processing ${actionLabels[action].toLowerCase()} for user #${userId}...</p>
        </div>
    `);

    // Simulate AJAX call
    fetch(`ajax_debug.php?action=${action}&id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTablerPopup('Action Completed', `
                    <div class="alert alert-success">
                        <div class="d-flex">
                            <div class="me-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="alert-title">Success!</h4>
                                <div class="text-muted">${data.message}</div>
                                ${data.new_password ? `<div class="mt-2"><strong>New Password:</strong> <code>${data.new_password}</code></div>` : ''}
                                ${data.new_status ? `<div class="mt-2"><strong>New Status:</strong> <span class="badge bg-blue">${data.new_status}</span></div>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="btn-list mt-3">
                        <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
                    </div>
                `);
            } else {
                updateTablerPopup('Action Failed', `
                    <div class="alert alert-danger">
                        <h4>Error</h4>
                        <p>${data.message || 'Unknown error occurred'}</p>
                    </div>
                    <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
                `);
            }
        })
        .catch(error => {
            updateTablerPopup('Network Error', `
                <div class="alert alert-danger">
                    <h4>Connection Failed</h4>
                    <p>Could not connect to server: ${error.message}</p>
                </div>
                <button class="btn btn-secondary" onclick="closeTablerPopup()">Close</button>
            `);
        });
}

function showLoadingPopup() {
    createTablerPopup('Processing', `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4>Please wait...</h4>
            <p class="text-muted">Processing your request. This may take a few moments.</p>
            <div class="progress mt-3">
                <div class="progress-bar progress-bar-indeterminate"></div>
            </div>
        </div>
    `, { hideCloseButton: true });

    // Auto-close after 3 seconds
    setTimeout(() => {
        updateTablerPopup('Complete', `
            <div class="text-center">
                <div class="text-success mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                </div>
                <h4>Processing Complete!</h4>
                <p class="text-muted">Your request has been processed successfully.</p>
                <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
            </div>
        `);
    }, 3000);
}

function testZIndexHierarchy() {
    // Show message
    showMessage('info', 'Testing Z-Index hierarchy...');
    
    // Create popup after a short delay
    setTimeout(() => {
        createTablerPopup('Z-Index Test', `
            <div class="alert alert-info">
                <h4>Z-Index Test Active</h4>
                <p>This popup should appear above all other elements including:</p>
                <ul>
                    <li>Sidebar navigation</li>
                    <li>Header bar</li>
                    <li>Dropdown menus</li>
                    <li>Previous messages</li>
                </ul>
                <p>If you can see this clearly, the z-index hierarchy is working correctly!</p>
            </div>
            <button class="btn btn-success" onclick="closeTablerPopup()">Z-Index Test Passed!</button>
        `);
    }, 1000);
}

// Form submission handler
function handleFormSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    updateTablerPopup('Form Submitted', `
        <div class="alert alert-success">
            <h4>Form Data Received</h4>
            <pre class="mt-2"><code>${JSON.stringify(data, null, 2)}</code></pre>
        </div>
        <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
    `);
}

function handleConfirm(confirmed) {
    if (confirmed) {
        updateTablerPopup('Deleted', `
            <div class="text-center">
                <div class="text-danger mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="64" height="64" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10"/>
                    </svg>
                </div>
                <h4>User Deleted</h4>
                <p class="text-muted">The user has been successfully removed from the system.</p>
                <button class="btn btn-primary" onclick="closeTablerPopup()">Close</button>
            </div>
        `);
    } else {
        closeTablerPopup();
    }
}

// Initialize tooltips and other Tabler components when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    console.log('Test popup page initialized with Tabler UI components');
});
</script>

<?php include 'includes/footer.php'; ?>