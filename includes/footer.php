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

        // Add active class to current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = '<?php echo $current_page; ?>';
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(function(link) {
                if (link.getAttribute('href').includes('page=' + currentPage)) {
                    link.classList.add('active');
                }
            });
        });

        // Sidebar minimize/maximize functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const pageWrapper = document.querySelector('.page-wrapper');
            const toggleBtn = document.getElementById('sidebarToggle');
            const expandBtn = document.getElementById('sidebarExpandBtn');
            const mobileToggle = document.querySelector('[data-bs-toggle="collapse"]');
            
            // Check if user preference is stored
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if (isMinimized && window.innerWidth > 768) {
                minimizeSidebar();
            }
            
            // Desktop minimize/maximize
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    if (sidebar.classList.contains('minimized')) {
                        maximizeSidebar();
                    } else {
                        minimizeSidebar();
                    }
                });
            }
            
            if (expandBtn) {
                expandBtn.addEventListener('click', function() {
                    maximizeSidebar();
                });
            }
            
            // Mobile toggle
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        toggleMobileSidebar();
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 768) {
                    // Mobile mode
                    sidebar.classList.remove('minimized');
                    pageWrapper.classList.remove('sidebar-minimized');
                    expandBtn.style.display = 'none';
                } else {
                    // Desktop mode
                    sidebar.classList.remove('mobile-open');
                    removeOverlay();
                    if (localStorage.getItem('sidebarMinimized') === 'true') {
                        minimizeSidebar();
                    }
                }
            });
            
            function minimizeSidebar() {
                sidebar.classList.add('minimized');
                pageWrapper.classList.add('sidebar-minimized');
                expandBtn.style.display = 'block';
                localStorage.setItem('sidebarMinimized', 'true');
                
                // Update toggle button icon
                if (toggleBtn) {
                    toggleBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="13,17 18,12 13,7"></polyline>
                            <polyline points="6,17 11,12 6,7"></polyline>
                        </svg>
                    `;
                    toggleBtn.title = 'Maximize Sidebar';
                }
            }
            
            function maximizeSidebar() {
                sidebar.classList.remove('minimized');
                pageWrapper.classList.remove('sidebar-minimized');
                expandBtn.style.display = 'none';
                localStorage.setItem('sidebarMinimized', 'false');
                
                // Update toggle button icon
                if (toggleBtn) {
                    toggleBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="11,17 6,12 11,7"></polyline>
                            <polyline points="18,17 13,12 18,7"></polyline>
                        </svg>
                    `;
                    toggleBtn.title = 'Minimize Sidebar';
                }
            }
            
            function toggleMobileSidebar() {
                if (sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    removeOverlay();
                } else {
                    sidebar.classList.add('mobile-open');
                    createOverlay();
                }
            }
            
            function createOverlay() {
                let overlay = document.querySelector('.sidebar-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    document.body.appendChild(overlay);
                    
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('mobile-open');
                        removeOverlay();
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

        // Success/Error message display
        function showMessage(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible`;
            alertDiv.innerHTML = `
                <div class="d-flex">
                    <div class="me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' : 'M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'}"/>
                        </svg>
                    </div>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            }
        }
    </script>
</body>
</html>