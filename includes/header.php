<?php 
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo htmlspecialchars($page_title); ?> - School Management System</title>
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg" rel="stylesheet"/>
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }
        
        /* Enhanced Z-Index System for Tabler UI */
        :root {
            --z-base: 1;
            --z-sidebar: 1030;
            --z-header: 1020;
            --z-dropdown: 1050;
            --z-modal-backdrop: 1055;
            --z-modal: 1060;
            --z-tooltip: 1070;
            --z-popup: 9999;
        }
        
        /* Tabler UI Sidebar Enhancements */
        .navbar-vertical {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 15rem;
            z-index: var(--z-sidebar) !important;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            transform: none !important;
            will-change: auto !important;
        }
        
        /* Minimized sidebar state */
        .navbar-vertical.minimized {
            width: 4rem;
            transform: none !important;
        }
        
        .navbar-vertical.minimized .nav-link-title,
        .navbar-vertical.minimized .navbar-brand .navbar-brand-image text,
        .navbar-vertical.minimized .hr-text {
            display: none !important;
        }
        
        /* Tabler UI Header */
        .navbar.navbar-expand-md {
            position: fixed;
            top: 0;
            right: 0;
            left: 15rem;
            z-index: var(--z-header) !important;
            height: 3.5rem;
            background: var(--tblr-bg-surface);
            border-bottom: 1px solid var(--tblr-border-color);
            backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            transform: none !important;
            will-change: auto !important;
        }
        
        .sidebar-minimized .navbar.navbar-expand-md {
            left: 4rem !important;
        }
        
        /* Page wrapper adjustments */
        .page-wrapper {
            margin-left: 15rem;
            min-height: 100vh;
            padding-top: 3.5rem;
            transition: all 0.3s ease;
            transform: none !important;
            will-change: auto !important;
        }
        
        .sidebar-minimized .page-wrapper {
            margin-left: 4rem !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 767.98px) {
            .navbar-vertical {
                left: -15rem;
                z-index: calc(var(--z-modal) + 10);
            }
            
            .navbar-vertical.show {
                left: 0;
                box-shadow: var(--tblr-box-shadow-lg);
            }
            
            .navbar.navbar-expand-md {
                left: 0 !important;
                right: 0;
            }
            
            .page-wrapper {
                margin-left: 0 !important;
                padding-top: 3.5rem;
            }
        }
        
        /* Dropdown menus */
        .navbar .dropdown-menu {
            z-index: var(--z-dropdown) !important;
            border: 1px solid var(--tblr-border-color);
            box-shadow: var(--tblr-box-shadow-dropdown);
            border-radius: var(--tblr-border-radius);
        }
        
        .navbar-vertical .dropdown-menu {
            z-index: var(--z-dropdown) !important;
        }
        
        /* Modal and popup fixes */
        .modal {
            z-index: var(--z-modal) !important;
        }
        
        .modal-backdrop {
            z-index: var(--z-modal-backdrop) !important;
        }
        
        .alert.auto-message,
        .custom-popup,
        .notification-popup,
        .toast {
            z-index: var(--z-popup) !important;
            position: fixed;
        }
        
        .tooltip {
            z-index: var(--z-tooltip) !important;
        }
        
        /* Custom popup container */
        .popup-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: var(--z-popup) !important;
            background: rgba(var(--tblr-body-color-rgb), 0.32);
            backdrop-filter: blur(4px);
        }
        
        .popup-content {
            pointer-events: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--tblr-bg-surface);
            border-radius: var(--tblr-border-radius-lg);
            box-shadow: var(--tblr-box-shadow-lg);
            padding: 1.5rem;
            max-width: 32rem;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--tblr-border-color);
        }
        
        /* Avatar and brand styling */
        .avatar {
            --tblr-avatar-size: 2rem;
            background: linear-gradient(135deg, var(--tblr-primary) 0%, var(--tblr-purple) 100%);
            color: white;
            font-weight: 600;
        }
        
        .avatar-lg {
            --tblr-avatar-size: 5rem;
            font-size: 1.5rem;
        }
        
        .navbar-brand-image {
            height: 2rem;
        }
        
        /* Enhanced button styles */
        .btn-ghost-secondary {
            color: var(--tblr-secondary);
            background: transparent;
            border: 1px solid transparent;
        }
        
        .btn-ghost-secondary:hover {
            color: var(--tblr-secondary);
            background: var(--tblr-secondary-lt);
            border-color: var(--tblr-secondary);
        }
        
        /* Notification badge */
        .badge-notification {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            padding: 0.25rem 0.375rem;
            font-size: 0.625rem;
            font-weight: 600;
        }
        
        /* Status indicators */
        .status-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-dot-animated {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 currentColor;
            }
            70% {
                box-shadow: 0 0 0 0.5rem transparent;
            }
            100% {
                box-shadow: 0 0 0 0 transparent;
            }
        }
        
        /* Enhanced card styles */
        .stats-card {
            transition: all 0.2s ease;
            border: 1px solid var(--tblr-border-color);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--tblr-box-shadow);
            border-color: var(--tblr-primary);
        }
        
        /* Performance optimizations */
        .navbar-vertical,
        .navbar.navbar-expand-md,
        .page-wrapper {
            transform: none !important;
            will-change: auto !important;
        }
        
        /* Focus states for accessibility */
        .nav-link:focus,
        .dropdown-item:focus,
        .btn:focus {
            outline: 2px solid var(--tblr-primary);
            outline-offset: 2px;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --tblr-theme: dark;
            }
        }
        
        /* Reduced motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Custom scrollbar for sidebar */
        .navbar-vertical::-webkit-scrollbar {
            width: 0.25rem;
        }
        
        .navbar-vertical::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .navbar-vertical::-webkit-scrollbar-thumb {
            background: var(--tblr-border-color);
            border-radius: 0.125rem;
        }
        
        .navbar-vertical::-webkit-scrollbar-thumb:hover {
            background: var(--tblr-border-color-dark);
        }
    </style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    <div class="page">
        <!-- Tabler UI Header -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <!-- Mobile menu toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Mobile brand -->
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="dashboard.php" class="d-flex align-items-center text-decoration-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="110" height="32" viewBox="0 0 232 68" class="navbar-brand-image">
                            <path d="M64 32C64 49.673 49.673 64 32 64C14.327 64 0 49.673 0 32C0 14.327 14.327 0 32 0C49.673 0 64 14.327 64 32Z" fill="var(--tblr-primary)"/>
                            <path d="M32 8C40.837 8 48 15.163 48 24C48 32.837 40.837 40 32 40C23.163 40 16 32.837 16 24C16 15.163 23.163 8 32 8Z" fill="white"/>
                            <text x="80" y="24" font-family="Inter, system-ui, sans-serif" font-size="14" font-weight="600" fill="currentColor">School</text>
                            <text x="80" y="44" font-family="Inter, system-ui, sans-serif" font-size="14" font-weight="600" fill="currentColor">Management</text>
                        </svg>
                    </a>
                </h1>
                
                <!-- Header navigation -->
                <div class="navbar-nav flex-row order-md-last">
                    <!-- Search (hidden on mobile) -->
                    <div class="nav-item d-none d-md-flex me-3">
                        <div class="btn-list">
                            <form action="?" method="get" autocomplete="off" novalidate>
                                <div class="input-group input-group-flat">
                                    <input type="text" class="form-control" placeholder="Search…" aria-label="Search" autocomplete="off">
                                    <span class="input-group-text">
                                        <a href="#" class="link-secondary" title="Search" data-bs-toggle="tooltip">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <circle cx="10" cy="10" r="7"/>
                                                <path d="M21 21l-6 -6"/>
                                            </svg>
                                        </a>
                                    </span>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/>
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
                            </svg>
                            <?php if (isset($role_specific_data['unread_notifications']) && $role_specific_data['unread_notifications'] > 0): ?>
                            <span class="badge bg-red badge-notification"><?php echo $role_specific_data['unread_notifications']; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Last updates</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-red d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">New student registration pending approval</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    2 minutes ago
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-green d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Grade submission completed</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    15 minutes ago
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-yellow d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">System maintenance scheduled</a>
                                                <div class="d-block text-muted text-truncate mt-n1">
                                                    1 hour ago
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="dashboard.php?page=notifications" class="btn btn-sm">View all notifications</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User menu -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: none">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="mt-1 small text-muted">
                                    <span class="badge badge-outline text-secondary"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></span>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <div class="dropdown-header">
                                <span class="text-muted">Signed in as</span>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="dashboard.php?page=profile">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>
                                </svg>
                                Profile
                            </a>
                            <a class="dropdown-item" href="dashboard.php?page=settings">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Settings
                            </a>
                            <a class="dropdown-item" href="dashboard.php?page=notifications">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/>
                                    <path d="M9 17v1a3 3 0 0 0 6 0v-1"/>
                                </svg>
                                Notifications
                                <?php if (isset($role_specific_data['unread_notifications']) && $role_specific_data['unread_notifications'] > 0): ?>
                                <span class="badge bg-red ms-auto"><?php echo $role_specific_data['unread_notifications']; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-red" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/>
                                    <path d="M7 12h14l-3 -3m0 6l3 -3"/>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>