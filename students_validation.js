
// Real-time validation for student forms
document.addEventListener('DOMContentLoaded', function() {
    const studentForm = document.querySelector('form[action*="students"]');
    if (!studentForm) return;
    
    const usernameInput = studentForm.querySelector('input[name="username"]');
    const emailInput = studentForm.querySelector('input[name="email"]');
    const studentIdInput = studentForm.querySelector('input[name="student_id"]');
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Check for duplicates
    const checkDuplicates = debounce(function() {
        const formData = new URLSearchParams();
        if (usernameInput?.value) formData.append('username', usernameInput.value);
        if (emailInput?.value) formData.append('email', emailInput.value);
        if (studentIdInput?.value) formData.append('student_id', studentIdInput.value);
        
        // Get exclude_user_id if editing
        const editUserId = studentForm.querySelector('input[name="edit_user_id"]');
        if (editUserId?.value) formData.append('exclude_user_id', editUserId.value);
        
        fetch(`students_ajax.php?action=check_duplicates&${formData.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear previous validation states
                    [usernameInput, emailInput, studentIdInput].forEach(input => {
                        if (input) {
                            input.classList.remove('is-invalid', 'is-valid');
                            const feedback = input.parentNode.querySelector('.invalid-feedback');
                            if (feedback) feedback.remove();
                        }
                    });
                    
                    // Show validation feedback
                    data.duplicates.forEach(field => {
                        let input;
                        let message;
                        
                        switch(field) {
                            case 'username':
                                input = usernameInput;
                                message = 'Username already exists';
                                break;
                            case 'email':
                                input = emailInput;
                                message = 'Email already exists';
                                break;
                            case 'student_id':
                                input = studentIdInput;
                                message = 'Student ID already exists';
                                break;
                        }
                        
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            feedback.textContent = message;
                            input.parentNode.appendChild(feedback);
                        }
                    });
                    
                    // Mark valid fields
                    [usernameInput, emailInput, studentIdInput].forEach(input => {
                        if (input && input.value && !input.classList.contains('is-invalid')) {
                            input.classList.add('is-valid');
                        }
                    });
                }
            })
            .catch(error => console.error('Validation error:', error));
    }, 500);
    
    // Attach event listeners
    [usernameInput, emailInput, studentIdInput].forEach(input => {
        if (input) {
            input.addEventListener('input', checkDuplicates);
            input.addEventListener('blur', checkDuplicates);
        }
    });
    
    // Student ID format validation
    if (studentIdInput) {
        studentIdInput.addEventListener('input', function() {
            const value = this.value.toUpperCase();
            // Auto-format student ID (e.g., STU2024001)
            if (value && !value.startsWith('STU')) {
                this.value = 'STU' + value;
            }
        });
    }
    
    // Phone number formatting
    const phoneInput = studentForm.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            if (value.length > 0) {
                // Format as +91-XXXXX-XXXXX
                if (value.length <= 5) {
                    this.value = '+91-' + value;
                } else {
                    this.value = '+91-' + value.slice(0, 5) + '-' + value.slice(5);
                }
            }
        });
    }
});
