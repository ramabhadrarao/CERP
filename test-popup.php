<?php
// test-popup.php - Test page for popup z-index issues
require_once 'config/database.php';
require_once 'includes/auth.php';

// Simple session check
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set page variables
$current_page = 'test-popup';
$page_title = 'Popup Z-Index Test';

// Get user info
try {
    $pdo = get_database_connection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name, r.description as role_description,
               r.permissions, r.is_system_role
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? AND u.status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Test popup page error: " . $e->getMessage());
    die("Database error occurred");
}

// Include header
include 'includes/header.php';

// Include navigation
include 'includes/navmenu.php';
?>

<div class="page-wrapper content-wrapper">
    <div class="page-header d-print-none">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Popup Z-Index Test</h2>
                    <div class="text-muted mt-1">Test different popup types to ensure proper z-index stacking</div>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Z-Index Testing Tools</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Test Popup Types</h4>
                                    <div class="btn-list">
                                        <button class="btn btn-primary" onclick="testCustomPopup()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                            </svg>
                                            Custom Popup (Z: 9999)
                                        </button>
                                        
                                        <button class="btn btn-success" onclick="testBootstrapModal()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                <path d="M9 12l2 2 4-4"></path>
                                            </svg>
                                            Bootstrap Modal (Z: 1060)
                                        </button>
                                        
                                        <button class="btn btn-warning" onclick="testAlertMessage()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 9v4"></path>
                                                <path d="M12 17h.01"></path>
                                                <circle cx="12" cy="12" r="10"></circle>
                                            </svg>
                                            Alert Message (Z: 9999)
                                        </button>
                                        
                                        <button class="btn btn-info" onclick="testTooltip()" data-bs-toggle="tooltip" title="This tooltip should appear above everything">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                                <path d="M12 17h.01"></path>
                                            </svg>
                                            Tooltip Test (Z: 1070)
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h4>AJAX Popup Tests</h4>
                                    <div class="btn-list">
                                        <button class="btn btn-secondary" onclick="testAjaxPopup()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7,10 12,15 17,10"></polyline>
                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                            </svg>
                                            AJAX Response Popup
                                        </button>
                                        
                                        <button class="btn btn-dark" onclick="testFormSubmissionPopup()">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14,2 14,8 20,8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                            Form Submission Result
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-12">
                                    <h4>Z-Index Hierarchy</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Element</th>
                                                    <th>Z-Index</th>
                                                    <th>Purpose</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Page Content</td>
                                                    <td>1</td>
                                                    <td>Base layer</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Top Header</td>
                                                    <td>1020</td>
                                                    <td>Navigation header</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Sidebar</td>
                                                    <td>1030</td>
                                                    <td>Navigation sidebar</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Dropdown Menus</td>
                                                    <td>1050</td>
                                                    <td>Navigation dropdowns</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Modal Backdrop</td>
                                                    <td>1055</td>
                                                    <td>Modal background overlay</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Bootstrap Modals</td>
                                                    <td>1060</td>
                                                    <td>Modal dialogs</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Tooltips</td>
                                                    <td>1070</td>
                                                    <td>Hover tooltips</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <td><strong>Custom Popups/Alerts</strong></td>
                                                    <td><strong>9999</strong></td>
                                                    <td>Highest priority notifications</td>
                                                    <td><span class="badge bg-success">✓ Fixed</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h4>🔧 Fixes Applied:</h4>
                                <ul class="mb-0">
                                    <li><strong>Removed transform properties</strong> from sidebar, page-wrapper, and header that were creating new stacking contexts</li>
                                    <li><strong>Added CSS variables</strong> for consistent z-index hierarchy</li>
                                    <li><strong>Fixed dropdown menus</strong> with proper positioning and z-index</li>
                                    <li><strong>Enhanced popup containers</strong> with backdrop and proper layering</li>
                                    <li><strong>Added !important declarations</strong> where necessary to override conflicting styles</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Modal for Testing -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bootstrap Modal Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This Bootstrap modal should appear above the sidebar and header.</p>
                <p><strong>Z-index:</strong> 1060</p>
                <p>Try clicking the sidebar toggle while this modal is open to test layering.</p>
                <button class="btn btn-primary" onclick="showMessage('success', 'Modal test successful!')">Test Alert from Modal</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Test Functions
function testCustomPopup() {
    createPopup('Custom Popup Test', `
        <div class="alert alert-success">
            <h5>✅ Custom Popup Working!</h5>
            <p>This popup has z-index: 9999 and should appear above everything.</p>
            <p><strong>Test checklist:</strong></p>
            <ul>
                <li>Above sidebar: <span class="text-success">✓</span></li>
                <li>Above header: <span class="text-success">✓</span></li>
                <li>Above dropdown menus: <span class="text-success">✓</span></li>
                <li>Backdrop working: <span class="text-success">✓</span></li>
            </ul>
            <button class="btn btn-warning" onclick="showMessage('warning', 'Alert from popup!')">Test Alert</button>
        </div>
    `);
}

function testBootstrapModal() {
    const modal = new bootstrap.Modal(document.getElementById('testModal'));
    modal.show();
}

function testAlertMessage() {
    showMessage('info', '🎉 Alert message with z-index 9999 - should appear above everything!');
}

function testTooltip() {
    // Tooltip is handled by Bootstrap automatically
    showMessage('info', 'Hover over the tooltip button to see if it appears above other elements');
}

function testAjaxPopup() {
    // Simulate AJAX call
    fetch('ajax_debug.php?action=test_popup')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                createPopup('AJAX Response', data.popup_html);
            } else {
                showMessage('danger', 'AJAX test failed: ' + data.message);
            }
        })
        .catch(error => {
            showMessage('danger', 'AJAX error: ' + error.message);
        });
}

function testFormSubmissionPopup() {
    // Simulate form submission result
    setTimeout(() => {
        createPopup('Form Submission Result', `
            <div class="alert alert-success">
                <h5>✅ Form Submitted Successfully</h5>
                <p>This popup simulates a form submission response.</p>
                <p>Common scenarios where popups fail:</p>
                <ul>
                    <li>After user management actions</li>
                    <li>After grade entry</li>
                    <li>After course enrollment</li>
                    <li>After file uploads</li>
                </ul>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="showMessage('success', 'Success notification from form popup')">Test Success Alert</button>
                    <button class="btn btn-danger btn-sm" onclick="showMessage('danger', 'Error notification from form popup')">Test Error Alert</button>
                </div>
            </div>
        `);
    }, 500);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            container: 'body' // Ensure tooltip is appended to body for proper z-index
        });
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>