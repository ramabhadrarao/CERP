</div>

    <!-- Footer -->
    <footer class="footer footer-transparent d-print-none">
        <div class="container-fluid">
            <div class="row text-center align-items-center flex-row-reverse">
                <div class="col-lg-auto ms-lg-auto">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            <a href="dashboard.php?page=reports" class="link-secondary">Reports</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="dashboard.php?page=settings" class="link-secondary">Settings</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="test_auth.php" class="link-secondary">Debug</a>
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
                            <a href="dashboard.php" class="link-secondary" rel="noopener">
                                Version 1.0
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    
    <script>
        // Global variables for sidebar management
        let sidebarState = {
            isMinimized: false,
            isMobile: false,
            autoCollapseEnabled: true
        };

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Enhanced Sidebar functionality with auto-adjustment
        // Enhanced Sidebar functionality with proper space utilization
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const pageWrapper = document.querySelector('.page-wrapper');
    const topHeader = document.querySelector('.navbar.sticky-top');
    const toggleBtn = document.getElementById('sidebarToggle');
    const expandBtn = document.getElementById('sidebarExpandBtn');
    const closeBtn = document.getElementById('sidebarClose');
    
    // Global sidebar state
    window.sidebarState = {
        isMinimized: false,
        isMobile: false,
        autoCollapseEnabled: true
    };
    
    // Initialize sidebar state
    initializeSidebar();
    
    // Add click handlers to all navigation links
    setupNavigationClickHandlers();
    
    // Desktop minimize/maximize handlers
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (window.sidebarState.isMinimized) {
                maximizeSidebar();
            } else {
                minimizeSidebar();
            }
        });
    }
    
    if (expandBtn) {
        expandBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            maximizeSidebar();
        });
    }
    
    // Mobile close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileSidebar();
        });
    }
    
    // Handle window resize with debouncing
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            handleWindowResize();
            forceLayoutRecalculation();
        }, 100);
    });
    
    // Initialize on load
    handleWindowResize();
    
    function initializeSidebar() {
        // Check user preference and screen size
        const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
        const autoCollapse = localStorage.getItem('sidebarAutoCollapse') !== 'false';
        
        window.sidebarState.autoCollapseEnabled = autoCollapse;
        
        if (window.innerWidth > 992 && isMinimized) {
            // Large screens: restore minimized state
            minimizeSidebar(false);
        } else if (window.innerWidth <= 992) {
            // Medium and small screens: ensure sidebar is closed initially
            closeMobileSidebar();
        }
        
        updateSidebarClasses();
        
        // Force initial layout calculation
        setTimeout(() => {
            forceLayoutRecalculation();
        }, 100);
    }
    
    function setupNavigationClickHandlers() {
        // Handle all navigation links
        const navLinks = sidebar.querySelectorAll('.nav-link:not(.dropdown-toggle)');
        const dropdownItems = sidebar.querySelectorAll('.dropdown-item');
        
        // Main navigation links
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                handleNavigationClick(this);
            });
        });
        
        // Dropdown items
        dropdownItems.forEach(item => {
            item.addEventListener('click', function(e) {
                handleNavigationClick(this);
            });
        });
    }
    
    function handleNavigationClick(clickedElement) {
        const href = clickedElement.getAttribute('href');
        
        // Skip if it's not a page navigation link
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return;
        }
        
        // Auto-collapse behavior based on screen size
        if (window.innerWidth <= 992) {
            // Mobile/tablet: always close sidebar
            closeMobileSidebar();
        } else if (window.innerWidth <= 1200 && window.sidebarState.autoCollapseEnabled) {
            // Medium screens: auto-minimize for better content view
            setTimeout(() => {
                minimizeSidebar();
            }, 150);
        }
        
        // Update active states
        updateActiveStates(clickedElement);
    }
    
    function updateActiveStates(clickedElement) {
        // Remove active class from all nav links
        const allNavLinks = sidebar.querySelectorAll('.nav-link, .dropdown-item');
        allNavLinks.forEach(link => {
            link.classList.remove('active');
        });
        
        // Add active class to clicked element
        clickedElement.classList.add('active');
        
        // If it's a dropdown item, also mark the parent dropdown as active
        const parentDropdown = clickedElement.closest('.dropdown');
        if (parentDropdown) {
            const parentToggle = parentDropdown.querySelector('.dropdown-toggle');
            if (parentToggle) {
                parentToggle.classList.add('active');
            }
        }
    }
    
    function handleWindowResize() {
        const currentWidth = window.innerWidth;
        const wasMobile = window.sidebarState.isMobile;
        window.sidebarState.isMobile = currentWidth <= 992;
        
        if (window.sidebarState.isMobile && !wasMobile) {
            // Switched to mobile
            sidebar.classList.remove('minimized');
            closeMobileSidebar();
            updateSidebarClasses();
        } else if (!window.sidebarState.isMobile && wasMobile) {
            // Switched to desktop
            sidebar.classList.remove('mobile-open');
            removeOverlay();
            
            // Restore desktop state
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if (isMinimized) {
                minimizeSidebar(false);
            } else {
                maximizeSidebar(false);
            }
        }
        
        updateSidebarClasses();
    }
    
    function minimizeSidebar(saveState = true) {
        if (window.sidebarState.isMobile) return;
        
        console.log('Minimizing sidebar...');
        
        window.sidebarState.isMinimized = true;
        
        // Apply classes immediately
        sidebar.classList.add('minimized');
        pageWrapper.classList.add('sidebar-minimized');
        if (topHeader) topHeader.classList.add('sidebar-minimized');
        document.body.classList.add('sidebar-minimized');
        
        if (saveState) {
            localStorage.setItem('sidebarMinimized', 'true');
        }
        
        // Show expand button
        if (expandBtn) expandBtn.style.display = 'block';
        
        // Update toggle button
        updateToggleButton();
        
        // Force layout recalculation
        setTimeout(() => {
            forceLayoutRecalculation();
            triggerResizeEvents();
        }, 50);
        
        console.log('Sidebar minimized, state:', window.sidebarState);
    }
    
    function maximizeSidebar(saveState = true) {
        if (window.sidebarState.isMobile) return;
        
        console.log('Maximizing sidebar...');
        
        window.sidebarState.isMinimized = false;
        
        // Remove classes immediately
        sidebar.classList.remove('minimized');
        pageWrapper.classList.remove('sidebar-minimized');
        if (topHeader) topHeader.classList.remove('sidebar-minimized');
        document.body.classList.remove('sidebar-minimized');
        
        if (saveState) {
            localStorage.setItem('sidebarMinimized', 'false');
        }
        
        // Hide expand button
        if (expandBtn) expandBtn.style.display = 'none';
        
        // Update toggle button
        updateToggleButton();
        
        // Force layout recalculation
        setTimeout(() => {
            forceLayoutRecalculation();
            triggerResizeEvents();
        }, 50);
        
        console.log('Sidebar maximized, state:', window.sidebarState);
    }
    
    function closeMobileSidebar() {
        sidebar.classList.remove('mobile-open');
        removeOverlay();
    }
    
    function updateSidebarClasses() {
        // Ensure proper classes are applied
        if (window.sidebarState.isMobile) {
            // Mobile mode
            sidebar.classList.remove('minimized');
            pageWrapper.classList.remove('sidebar-minimized');
            if (topHeader) topHeader.classList.remove('sidebar-minimized');
            document.body.classList.remove('sidebar-minimized');
            if (expandBtn) expandBtn.style.display = 'none';
        } else {
            // Desktop mode
            if (window.sidebarState.isMinimized) {
                sidebar.classList.add('minimized');
                pageWrapper.classList.add('sidebar-minimized');
                if (topHeader) topHeader.classList.add('sidebar-minimized');
                document.body.classList.add('sidebar-minimized');
                if (expandBtn) expandBtn.style.display = 'block';
            } else {
                sidebar.classList.remove('minimized');
                pageWrapper.classList.remove('sidebar-minimized');
                if (topHeader) topHeader.classList.remove('sidebar-minimized');
                document.body.classList.remove('sidebar-minimized');
                if (expandBtn) expandBtn.style.display = 'none';
            }
        }
    }
    
    function updateToggleButton() {
        if (!toggleBtn || window.sidebarState.isMobile) return;
        
        if (window.sidebarState.isMinimized) {
            toggleBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="13,17 18,12 13,7"></polyline>
                    <polyline points="6,17 11,12 6,7"></polyline>
                </svg>
            `;
            toggleBtn.title = 'Expand Sidebar';
        } else {
            toggleBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="11,17 6,12 11,7"></polyline>
                    <polyline points="18,17 13,12 18,7"></polyline>
                </svg>
            `;
            toggleBtn.title = 'Minimize Sidebar';
        }
    }
    
    function forceLayoutRecalculation() {
        // Force browser to recalculate layout
        pageWrapper.style.transform = 'translateZ(0)';
        pageWrapper.offsetHeight; // Trigger reflow
        pageWrapper.style.transform = '';
        
        // Recalculate all responsive elements
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            table.style.width = '';
            table.offsetWidth; // Trigger reflow
            table.style.width = '100%';
        });
        
        // Recalculate card layouts
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.style.maxWidth = '';
            card.offsetWidth; // Trigger reflow
        });
        
        console.log('Layout recalculated, sidebar state:', window.sidebarState);
    }
    
    function triggerResizeEvents() {
        // Trigger resize events for any responsive components
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
            window.dispatchEvent(new CustomEvent('sidebarResize', {
                detail: {
                    isMinimized: window.sidebarState.isMinimized,
                    isMobile: window.sidebarState.isMobile
                }
            }));
        }, 100);
    }
    
    function createOverlay() {
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', function() {
                closeMobileSidebar();
            });
        }
        overlay.classList.add('show');
    }
    
    function removeOverlay() {
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
    
    // Global function for mobile toggle
    window.toggleMobileSidebar = function() {
        if (window.sidebarState.isMobile) {
            if (sidebar.classList.contains('mobile-open')) {
                closeMobileSidebar();
            } else {
                sidebar.classList.add('mobile-open');
                createOverlay();
            }
        }
    };
    
    // Global function to toggle auto-collapse feature
    window.toggleAutoCollapse = function() {
        window.sidebarState.autoCollapseEnabled = !window.sidebarState.autoCollapseEnabled;
        localStorage.setItem('sidebarAutoCollapse', window.sidebarState.autoCollapseEnabled.toString());
        
        showMessage('info', `Auto-collapse ${window.sidebarState.autoCollapseEnabled ? 'enabled' : 'disabled'}`);
    };
    
    // Debug function
    window.getSidebarState = function() {
        console.log('Current Sidebar State:', window.sidebarState);
        console.log('DOM Classes:', {
            sidebar: sidebar.className,
            pageWrapper: pageWrapper.className,
            topHeader: topHeader ? topHeader.className : 'N/A'
        });
        console.log('Computed Styles:', {
            sidebarWidth: getComputedStyle(sidebar).width,
            pageWrapperMarginLeft: getComputedStyle(pageWrapper).marginLeft,
            pageWrapperWidth: getComputedStyle(pageWrapper).width
        });
    };
    
    // Force proper initialization after DOM is fully loaded
    setTimeout(() => {
        updateSidebarClasses();
        forceLayoutRecalculation();
        console.log('Sidebar initialized with state:', window.sidebarState);
    }, 200);
});

// Enhanced active page detection
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.search.includes('page=') ? 
        new URLSearchParams(window.location.search).get('page') : 'home';
    
    const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');
    
    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && href.includes('page=' + currentPage)) {
            link.classList.add('active');
            
            // If it's a dropdown item, also mark the parent dropdown as active
            const parentDropdown = link.closest('.dropdown');
            if (parentDropdown) {
                const parentToggle = parentDropdown.querySelector('.dropdown-toggle');
                if (parentToggle) {
                    parentToggle.classList.add('active');
                }
            }
        }
    });
});

// Enhanced message display
function showMessage(type, message) {
    const existingMessages = document.querySelectorAll('.alert.auto-message');
    existingMessages.forEach(msg => msg.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible auto-message`;
    alertDiv.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    alertDiv.innerHTML = `
        <div class="d-flex">
            <div class="me-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : type === 'info' ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                </svg>
            </div>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

        // Enhanced active page detection
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo $current_page; ?>';
            const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');
            
            navLinks.forEach(function(link) {
                const href = link.getAttribute('href');
                if (href && href.includes('page=' + currentPage)) {
                    link.classList.add('active');
                    
                    // If it's a dropdown item, also mark the parent dropdown as active
                    const parentDropdown = link.closest('.dropdown');
                    if (parentDropdown) {
                        const parentToggle = parentDropdown.querySelector('.dropdown-toggle');
                        if (parentToggle) {
                            parentToggle.classList.add('active');
                        }
                    }
                }
            });
        });

        // Content adjustment for responsive elements
        window.addEventListener('resize', function() {
            // Adjust any charts, tables, or other responsive elements
            const tables = document.querySelectorAll('.table-responsive');
            tables.forEach(table => {
                // Force table to recalculate its responsive behavior
                table.style.overflowX = 'auto';
            });
            
            // Trigger custom resize events for any custom components
            window.dispatchEvent(new CustomEvent('sidebarResize', {
                detail: {
                    isMinimized: sidebarState?.isMinimized || false,
                    isMobile: sidebarState?.isMobile || false
                }
            }));
        });

        // Confirmation dialogs for delete actions
        function confirmDelete(item, name) {
            return confirm('Are you sure you want to delete ' + item + ' "' + name + '"? This action cannot be undone.');
        }

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }

        // Enhanced message display
        function showMessage(type, message) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.alert.auto-message');
            existingMessages.forEach(msg => msg.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible auto-message`;
            alertDiv.style.position = 'fixed';
            alertDiv.style.top = '80px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.style.minWidth = '300px';
            alertDiv.innerHTML = `
                <div class="d-flex">
                    <div class="me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : type === 'info' ? 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                        </svg>
                    </div>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv && alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Prevent double form submission
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
                        
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }, 3000);
                    }
                });
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Debug function for sidebar state
        window.getSidebarState = function() {
            console.log('Sidebar State:', sidebarState);
            console.log('Local Storage:', {
                minimized: localStorage.getItem('sidebarMinimized'),
                autoCollapse: localStorage.getItem('sidebarAutoCollapse')
            });
        };
        
        // Reset sidebar settings function
        window.resetSidebarSettings = function() {
            if (confirm('Reset sidebar settings to default? This will expand the sidebar and enable auto-collapse.')) {
                localStorage.removeItem('sidebarMinimized');
                localStorage.removeItem('sidebarAutoCollapse');
                
                // Refresh the page to apply changes
                window.location.reload();
            }
        };
    </script>
</body>
</html>