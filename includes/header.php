<?php 
ob_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons-sprite.svg" rel="stylesheet"/>
    <style>
        /* Basic Brand Styling */
        .navbar-brand-image {
            width: 32px;
            height: 32px;
            background: #206bc4;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* User Avatar Styles */
        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .user-avatar-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 2rem;
        }
        
        /* Badge and Card Styles */
        .role-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .content-wrapper {
            min-height: calc(100vh - 200px);
        }
        
        /* Enhanced Sidebar Styles with Better Transitions */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            z-index: 1030;
            background: white;
            border-right: 1px solid #e6e7e9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
        }
        
        /* Sidebar Minimized State - FIXED */
        #sidebar.minimized {
            width: 70px;
        }
        
        /* Hide text elements when minimized - IMPROVED */
        #sidebar.minimized .nav-link-title,
        #sidebar.minimized .sidebar-title {
            display: none !important;
            visibility: hidden;
        }
        
        #sidebar.minimized .hr-text {
            display: none !important;
        }
        
        /* Fix nav-link layout in minimized state */
        #sidebar.minimized .nav-link {
            justify-content: center !important;
            padding: 0.75rem 1rem !important;
            margin: 2px 8px !important;
            position: relative;
            min-height: 44px;
            display: flex !important;
            align-items: center !important;
        }
        
        /* Ensure icons are centered and visible */
        #sidebar.minimized .nav-link-icon {
            margin-right: 0 !important;
            margin-left: 0 !important;
            width: 24px;
            height: 24px;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        #sidebar.minimized .nav-link-icon svg {
            width: 20px !important;
            height: 20px !important;
        }
        
        /* Hide dropdown arrows when minimized */
        #sidebar.minimized .dropdown-toggle::after {
            display: none !important;
        }
        
        /* Fix dropdown menu positioning in minimized sidebar */
        #sidebar.minimized .dropdown-menu {
            position: fixed !important;
            left: 70px !important;
            top: auto !important;
            margin: 0 !important;
            border: 1px solid #e6e7e9;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            z-index: 1050 !important;
            min-width: 200px;
            border-radius: 8px;
        }
        
        /* Show dropdown on hover for minimized sidebar */
        #sidebar.minimized .nav-item.dropdown:hover .dropdown-menu {
            display: block !important;
        }
        
        /* Tooltip-like behavior for minimized sidebar - IMPROVED */
        #sidebar.minimized .nav-link[title]:hover::after {
            content: attr(title);
            position: fixed;
            left: 75px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1060;
            pointer-events: none;
            top: 50%;
            transform: translateY(-50%);
            animation: tooltipFadeIn 0.2s ease;
        }
        
        @keyframes tooltipFadeIn {
            from { opacity: 0; transform: translateY(-50%) translateX(-5px); }
            to { opacity: 1; transform: translateY(-50%) translateX(0); }
        }
        
        /* FIXED: Page Wrapper with proper space utilization */
        .page-wrapper {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            width: calc(100vw - 280px);
        }
        
        .page-wrapper.sidebar-minimized {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
        }
        
        /* FIXED: Top Header positioning */
        .navbar.sticky-top {
            position: fixed;
            top: 0;
            right: 0;
            left: 280px;
            z-index: 1020;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e6e7e9;
            height: 60px;
            width: calc(100vw - 280px);
        }
        
        .sidebar-minimized .navbar.sticky-top {
            left: 70px !important;
            width: calc(100vw - 70px) !important;
        }
        
        /* Enhanced Page Content */
        .page-header {
            margin-top: 60px;
            padding: 1.5rem 0;
            transition: all 0.3s ease;
        }
        
        .page-body {
            padding-top: 0;
            padding-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        /* FIXED: Container adjustments for better space usage */
        .container-fluid {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            transition: all 0.3s ease;
            width: 100%;
            max-width: none;
        }
        
        /* Enhanced Cards and Tables for better space utilization */
        .card {
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .table-responsive {
            transition: all 0.3s ease;
            border-radius: 8px;
            width: 100%;
        }
        
        /* Responsive Grid Adjustments - IMPROVED */
        .row-deck .col,
        .row-cards .col {
            transition: all 0.3s ease;
        }
        
        /* Better space utilization when sidebar is minimized */
        @media (min-width: 992px) {
            .sidebar-minimized .col-lg-3 {
                flex: 0 0 auto;
                width: 24% !important;
                max-width: 24%;
            }
            
            .sidebar-minimized .col-lg-4 {
                flex: 0 0 auto;
                width: 32% !important;
                max-width: 32%;
            }
            
            .sidebar-minimized .col-lg-6 {
                flex: 0 0 auto;
                width: 49% !important;
                max-width: 49%;
            }
            
            .sidebar-minimized .col-lg-8 {
                flex: 0 0 auto;
                width: 66% !important;
                max-width: 66%;
            }
            
            .sidebar-minimized .col-lg-9 {
                flex: 0 0 auto;
                width: 74% !important;
                max-width: 74%;
            }
            
            .sidebar-minimized .col-lg-12 {
                flex: 0 0 auto;
                width: 100% !important;
                max-width: 100%;
            }
        }
        
        /* Sidebar Expand Button */
        .sidebar-minimized-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1035;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: scale(0.8);
        }
        
        .sidebar-minimized .sidebar-minimized-toggle {
            left: 20px;
            opacity: 1;
            transform: scale(1);
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 992px) {
            #sidebar {
                left: -280px;
                z-index: 1050;
                box-shadow: none;
            }
            
            #sidebar.mobile-open {
                left: 0;
                box-shadow: 2px 0 16px rgba(0, 0, 0, 0.1);
            }
            
            .page-wrapper {
                margin-left: 0 !important;
                width: 100vw !important;
            }
            
            .navbar.sticky-top {
                left: 0 !important;
                width: 100vw !important;
            }
            
            .page-header {
                margin-top: 60px;
                padding: 1rem 0;
            }
            
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }
            
            .sidebar-minimized-toggle {
                display: none !important;
            }
            
            /* Reset grid on mobile */
            .col-lg-3, .col-lg-4, .col-lg-6, .col-lg-8, .col-lg-9, .col-lg-12 {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
        
        /* Navigation Improvements */
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .sidebar-nav .nav-link {
            border-radius: 8px;
            margin: 2px 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            min-height: 44px;
        }
        
        .sidebar-nav .nav-link-icon {
            margin-right: 0.75rem;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .sidebar-nav .nav-link-icon svg {
            width: 20px;
            height: 20px;
        }
        
        .sidebar-nav .nav-link-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, #206bc4, #1a5aa0);
            color: white;
            box-shadow: 0 2px 8px rgba(32, 107, 196, 0.3);
        }
        
        .sidebar-nav .nav-link:hover:not(.active) {
            background-color: rgba(32, 107, 196, 0.1);
            transform: translateX(4px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Dropdown Menu Improvements */
        .dropdown-menu {
            border: 1px solid #e6e7e9;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.5rem 0;
            margin-top: 0.25rem;
            min-width: 200px;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            border-radius: 4px;
            margin: 0 0.25rem;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item.active {
            background-color: #206bc4;
            color: white;
        }
        
        .dropdown-item:hover:not(.active) {
            background-color: rgba(32, 107, 196, 0.1);
            transform: translateX(2px);
        }
        
        /* Toggle Button Styles */
        #sidebarToggle, #sidebarClose {
            border: none;
            background: transparent;
            color: #6c757d;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 6px;
            padding: 0.375rem;
            position: relative;
            overflow: hidden;
        }
        
        #sidebarToggle:hover, #sidebarClose:hover {
            color: #206bc4;
            background: rgba(32, 107, 196, 0.1);
            transform: scale(1.05);
        }
        
        /* Brand adjustments for minimized state */
        #sidebar.minimized .navbar-brand {
            justify-content: center;
            padding: 1rem 0;
        }
        
        #sidebar.minimized .navbar-brand a {
            justify-content: center;
        }
        
        /* Scrollbar Styling */
        #sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        #sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
            transition: background 0.2s ease;
        }
        
        #sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.2);
        }
        
        /* Performance optimizations */
        .page-wrapper,
        #sidebar,
        .navbar.sticky-top {
            will-change: transform, width, left, margin-left;
        }
        
        /* Ensure proper stacking and visibility */
        #sidebar * {
            box-sizing: border-box;
        }
        
        /* Force proper display for minimized icons */
        #sidebar.minimized .nav-item {
            display: block !important;
        }
        
        #sidebar.minimized .nav-link {
            display: flex !important;
            visibility: visible !important;
        }
        
        /* Better spacing for minimized state */
        #sidebar.minimized .navbar-brand {
            margin-bottom: 0.5rem;
        }
        
        #sidebar.minimized .sidebar-nav {
            padding-top: 0.5rem;
        }
        
        /* Enhanced Stats Cards */
        .stats-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e6e7e9;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            border-color: #206bc4;
        }
        
        /* Focus states for accessibility */
        .nav-link:focus,
        .dropdown-item:focus,
        #sidebarToggle:focus,
        #sidebarClose:focus {
            outline: 2px solid #206bc4;
            outline-offset: 2px;
        }
        
        /* Reduced motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Top Header -->
        <header class="navbar navbar-expand-md navbar-light">
            <div class="container-fluid">
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" onclick="toggleMobileSidebar()">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="navbar-brand d-lg-none">
                    <a href="dashboard.php" class="d-flex align-items-center text-decoration-none">
                        <div class="navbar-brand-image me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 9L12 5 2 9l10 4 10-4zM6 10.5v7c0 2.5 2.5 4.5 6 4.5s6-2 6-4.5v-7"/>
                            </svg>
                        </div>
                        <span>School Management</span>
                    </a>
                </div>
                
                <div class="navbar-nav flex-row order-md-last ms-auto">
                    <!-- Notifications -->
                    <div class="nav-item dropdown me-3">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="position-relative">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span class="badge bg-red badge-notification badge-pill">3</span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Notifications</h3>
                                </div>
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot status-dot-animated bg-red d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">New student registration</a>
                                                <div class="d-block text-muted text-truncate mt-n1">2 minutes ago</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                            </div>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="mt-1 small text-muted">
                                    <span class="role-badge bg-blue-lt"><?php echo ucfirst(str_replace('_', ' ', $user['role_name'] ?? 'user')); ?></span>
                                </div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="dashboard.php?page=profile" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                Profile
                            </a>
                            <a href="dashboard.php?page=settings" class="dropdown-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                                Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-danger">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16,17 21,12 16,7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>