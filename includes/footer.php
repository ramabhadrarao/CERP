</div>
    </div>
    
    <!-- Tabler UI Footer -->
    <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
                <div class="col-lg-auto ms-lg-auto">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            <a href="dashboard.php?page=reports" class="link-secondary" rel="noopener">
                                Reports
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="dashboard.php?page=settings" class="link-secondary" rel="noopener">
                                Settings
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="https://github.com/tabler/tabler" target="_blank" class="link-secondary" rel="noopener">
                                Source code
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="test_auth.php" class="link-secondary" rel="noopener">
                                Debug
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            Copyright &copy; 2025
                            <a href="dashboard.php" class="link-secondary">School Management System</a>.
                            All rights reserved.
                        </li>
                        <li class="list-inline-item">
                            <a href="https://tabler.io" target="_blank" class="link-secondary" rel="noopener">
                                v1.0.0-beta17
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</div>

<!-- Core Tabler JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>

<script>
// Enhanced Tabler UI JavaScript with proper sidebar management
document.addEventListener('DOMContentLoaded', function() {
    
    // Global sidebar state management
    window.sidebarState = {
        isMinimized: false,
        isMobile: window.innerWidth <= 767,
        autoCollapseEnabled: localStorage.getItem('sidebarAutoCollapse') !== 'false'
    };
    
    // Initialize Tabler components
    initializeTablerComponents();
    
    // Initialize sidebar functionality
    initializeSidebar();
    
    // Initialize navigation handlers
    initializeNavigation();
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleWindowResize, 150);
    });
    
    function initializeTablerComponents() {
        // Initialize all Tabler dropdowns
        const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl, {
                boundary: 'viewport',
                autoClose: true
            });
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                container: 'body'
            });
        });
        
        // Initialize modals
        const modalElementList = [].slice.call(document.querySelectorAll('.modal'));
        const modalList = modalElementList.map(function (modalEl) {
            return new bootstrap.Modal(modalEl);
        });
        
        console.log('Tabler components initialized:', {
            dropdowns: dropdownList.length,
            tooltips: tooltipList.length,
            modals: modalList.length
        });
    }
    
    function initializeSidebar() {
        const sidebar = document.querySelector('.navbar-vertical');
        const pageWrapper = document.querySelector('.page-wrapper');
        const header = document.querySelector('.navbar.navbar-expand-md');
        
        if (!sidebar || !pageWrapper) return;
        
        // Restore sidebar state from localStorage
        const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
        if (isMinimized && !window.sidebarState.isMobile) {
            minimizeSidebar(false);
        }
        
        // Handle mobile sidebar toggle
        const mobileToggle = document.querySelector('.navbar-toggler');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                if (window.sidebarState.isMobile) {
                    sidebar.classList.toggle('show');
                    updateMobileOverlay();
                }
            });
        }
        
        // Add sidebar toggle button for desktop
        addSidebarToggleButton();
    }
    
    function addSidebarToggleButton() {
        const header = document.querySelector('.navbar.navbar-expand-md .container-xl');
        if (!header || document.getElementById('sidebarToggle')) return;
        
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'sidebarToggle';
        toggleBtn.className = 'btn btn-ghost-secondary btn-sm d-none d-md-flex me-3';
        toggleBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        `;
        toggleBtn.title = 'Toggle Sidebar';
        
        toggleBtn.addEventListener('click', function() {
            if (window.sidebarState.isMinimized) {
                maximizeSidebar();
            } else {
                minimizeSidebar();
            }
        });
        
        // Insert at the beginning of header container
        header.insertBefore(toggleBtn, header.firstChild);
    }
    
    function minimizeSidebar(saveState = true) {
        if (window.sidebarState.isMobile) return;
        
        const sidebar = document.querySelector('.navbar-vertical');
        const pageWrapper = document.querySelector('.page-wrapper');
        const header = document.querySelector('.navbar.navbar-expand-md');
        
        window.sidebarState.isMinimized = true;
        
        sidebar.classList.add('minimized');
        pageWrapper.classList.add('sidebar-minimized');
        header.classList.add('sidebar-minimized');
        document.body.classList.add('sidebar-minimized');
        
        if (saveState) {
            localStorage.setItem('sidebarMinimized', 'true');
        }
        
        updateToggleButton();
        triggerLayoutUpdate();
        
        console.log('Sidebar minimized');
    }
    
    function maximizeSidebar(saveState = true) {
        if (window.sidebarState.isMobile) return;
        
        const sidebar = document.querySelector('.navbar-vertical');
        const pageWrapper = document.querySelector('.page-wrapper');
        const header = document.querySelector('.navbar.navbar-expand-md');
        
        window.sidebarState.isMinimized = false;
        
        sidebar.classList.remove('minimized');
        pageWrapper.classList.remove('sidebar-minimized');
        header.classList.remove('sidebar-minimized');
        document.body.classList.remove('sidebar-minimized');
        
        if (saveState) {
            localStorage.setItem('sidebarMinimized', 'false');
        }
        
        updateToggleButton();
        triggerLayoutUpdate();
        
        console.log('Sidebar maximized');
    }
    
    function updateToggleButton() {
        const toggleBtn = document.getElementById('sidebarToggle');
        if (!toggleBtn) return;
        
        if (window.sidebarState.isMinimized) {
            toggleBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="9" y1="12" x2="21" y2="12"/>
                    <line x1="9" y1="18" x2="21" y2="18"/>
                </svg>
            `;
            toggleBtn.title = 'Expand Sidebar';
        } else {
            toggleBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            `;
            toggleBtn.title = 'Minimize Sidebar';
        }
    }
    
    function handleWindowResize() {
        const wasMobile = window.sidebarState.isMobile;
        window.sidebarState.isMobile = window.innerWidth <= 767;
        
        if (window.sidebarState.isMobile && !wasMobile) {
            // Switched to mobile
            const sidebar = document.querySelector('.navbar-vertical');
            sidebar.classList.remove('minimized');
            sidebar.classList.remove('show');
            removeDesktopClasses();
            removeOverlay();
        } else if (!window.sidebarState.isMobile && wasMobile) {
            // Switched to desktop
            const sidebar = document.querySelector('.navbar-vertical');
            sidebar.classList.remove('show');
            removeOverlay();
            
            // Restore desktop sidebar state
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if (isMinimized) {
                minimizeSidebar(false);
            }
        }
        
        updateToggleButton();
        triggerLayoutUpdate();
    }
    
    function removeDesktopClasses() {
        const pageWrapper = document.querySelector('.page-wrapper');
        const header = document.querySelector('.navbar.navbar-expand-md');
        
        pageWrapper.classList.remove('sidebar-minimized');
        header.classList.remove('sidebar-minimized');
        document.body.classList.remove('sidebar-minimized');
    }
    
    function updateMobileOverlay() {
        const sidebar = document.querySelector('.navbar-vertical');
        
        if (sidebar.classList.contains('show')) {
            createOverlay();
        } else {
            removeOverlay();
        }
    }
    
    function createOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(var(--tblr-body-color-rgb), 0.32);
                z-index: calc(var(--z-modal) + 5);
                backdrop-filter: blur(4px);
            `;
            
            overlay.addEventListener('click', function() {
                const sidebar = document.querySelector('.navbar-vertical');
                sidebar.classList.remove('show');
                removeOverlay();
            });
            
            document.body.appendChild(overlay);
        }
    }
    
    function removeOverlay() {
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    function triggerLayoutUpdate() {
        // Trigger resize events for responsive components
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
            window.dispatchEvent(new CustomEvent('sidebarToggle', {
                detail: window.sidebarState
            }));
        }, 100);
    }
    
    function initializeNavigation() {
        // Set active navigation states
        const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
        setActiveNavigation(currentPage);
        
        // Handle navigation clicks
        const navLinks = document.querySelectorAll('.navbar-vertical .nav-link, .navbar-vertical .dropdown-item');
        navLinks.forEach(link => {
            if (link.getAttribute('href') && !link.getAttribute('href').startsWith('#')) {
                link.addEventListener('click', function() {
                    if (window.sidebarState.isMobile) {
                        setTimeout(() => {
                            const sidebar = document.querySelector('.navbar-vertical');
                            sidebar.classList.remove('show');
                            removeOverlay();
                        }, 150);
                    }
                });
            }
        });
    }
    
    function setActiveNavigation(currentPage) {
        const sidebar = document.querySelector('.navbar-vertical');
        if (!sidebar) return;
        
        // Remove all active states
        sidebar.querySelectorAll('.nav-item, .dropdown-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Set active states
        const navLinks = sidebar.querySelectorAll('.nav-link, .dropdown-item');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && (
                (currentPage === 'home' && href === 'dashboard.php') ||
                href.includes('page=' + currentPage)
            )) {
                const navItem = link.closest('.nav-item');
                navItem.classList.add('active');
                
                // If it's a dropdown item, show parent dropdown
                const parentDropdown = link.closest('.nav-item.dropdown');
                if (parentDropdown) {
                    parentDropdown.classList.add('show');
                }
            }
        });
    }
    
    // Enhanced message display with Tabler styling
    window.showMessage = function(type, message, duration = 3000) {
        // Remove existing messages
        document.querySelectorAll('.alert.auto-message').forEach(msg => msg.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible auto-message`;
        alertDiv.style.cssText = `
            position: fixed !important;
            top: 4rem !important;
            right: 1rem !important;
            z-index: var(--z-popup) !important;
            min-width: 20rem;
            max-width: 32rem;
            box-shadow: var(--tblr-box-shadow-lg);
            border: 1px solid var(--tblr-border-color);
            backdrop-filter: blur(8px);
            animation: slideInRight 0.3s ease;
        `;
        
        alertDiv.innerHTML = `
            <div class="d-flex">
                <div class="me-2">
                    ${getAlertIcon(type)}
                </div>
                <div class="flex-fill">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss
        setTimeout(() => {
            if (alertDiv && alertDiv.parentNode) {
                alertDiv.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, duration);
        
        return alertDiv;
    };
    
    function getAlertIcon(type) {
        const icons = {
            success: '<svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>',
            danger: '<svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>',
            info: '<svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11,12 12,12 12,16 13,16"/></svg>'
        };
        return icons[type] || icons.info;
    }
    
    // Custom popup functionality with Tabler styling
    window.createPopup = function(title, content, options = {}) {
        // Remove existing popups
        document.querySelectorAll('.custom-popup-container').forEach(el => el.remove());
        
        const popup = document.createElement('div');
        popup.className = 'custom-popup-container popup-container';
        popup.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(var(--tblr-body-color-rgb), 0.32) !important;
            z-index: var(--z-popup) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease;
        `;
        
        popup.innerHTML = `
            <div class="popup-content card" style="
                max-width: 32rem;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
                margin: 0;
                animation: scaleIn 0.2s ease;
            ">
                <div class="card-header">
                    <h3 class="card-title">${title}</h3>
                    <div class="card-actions">
                        <button class="btn-close" onclick="closePopup()" aria-label="Close"></button>
                    </div>
                </div>
                <div class="card-body">${content}</div>
            </div>
        `;
        
        // Close on backdrop click
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closePopup();
            }
        });
        
        document.body.appendChild(popup);
        
        // Auto-close if specified
        if (options.autoClose) {
            setTimeout(closePopup, options.autoClose);
        }
        
        return popup;
    };
    
    window.closePopup = function() {
        const popups = document.querySelectorAll('.custom-popup-container');
        popups.forEach(popup => {
            popup.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => popup.remove(), 200);
        });
    };
    
    // Auto-hide alerts with fade out
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert:not(.auto-message)');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentNode) alert.remove();
            }, 500);
        });
    }, 5000);
    
    // Global utility functions
    window.getSidebarState = function() {
        return window.sidebarState;
    };
    
    window.toggleSidebar = function() {
        if (window.sidebarState.isMinimized) {
            maximizeSidebar();
        } else {
            minimizeSidebar();
        }
    };
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    console.log('Tabler UI system initialized successfully');
});

// Form validation and submission handling
document.addEventListener('DOMContentLoaded', function() {
    // Prevent double form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = `
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Loading...
                `;
                
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });
    });
});

// Confirmation dialogs with Tabler styling
function confirmDelete(item, name) {
    return confirm(`Are you sure you want to delete ${item} "${name}"?\n\nThis action cannot be undone.`);
}

// Debug functions for development
if (window.location.search.includes('debug=1')) {
    console.log('Debug mode enabled');
    
    // Add debug button
    const debugBtn = document.createElement('button');
    debugBtn.className = 'btn btn-warning btn-sm';
    debugBtn.style.cssText = 'position: fixed; bottom: 1rem; right: 1rem; z-index: 9999;';
    debugBtn.innerHTML = 'Debug';
    debugBtn.onclick = () => {
        console.log('Sidebar State:', window.getSidebarState());
        showMessage('info', 'Check console for debug information');
    };
    document.body.appendChild(debugBtn);
}
</script>
</body>
</html>