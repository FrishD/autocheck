// Modern Clean JavaScript for Auth Forms
document.addEventListener('DOMContentLoaded', function() {
    // Registration code input handling
    setupCodeInputs();
    
    // Setup password strength meter
    setupPasswordStrength();

    // Prevent form resubmission
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});


// Setup 6-digit code inputs for registration
function setupCodeInputs() {
    const codeInputContainer = document.querySelector('.code-input-container');
    
    if (codeInputContainer) {
        const inputs = codeInputContainer.querySelectorAll('input');
        
        inputs.forEach((input, index) => {
            // Focus next input on input
            input.addEventListener('input', function(e) {
                const value = e.target.value;
                
                if (value.length === 1) {
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    } else {
                        // Last input filled, submit the form automatically
                        const form = codeInputContainer.closest('form');
                        if (form) {
                            // Add a small delay for better UX
                            setTimeout(() => {
                                showVerificationAnimation();
                                setTimeout(() => {
                                    form.submit();
                                }, 1000);
                            }, 300);
                        }
                    }
                }
            });
            
            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            
            // Handle paste for the entire code
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text');
                const cleanData = pasteData.replace(/\D/g, '').slice(0, inputs.length);
                
                cleanData.split('').forEach((char, i) => {
                    if (i < inputs.length) {
                        inputs[i].value = char;
                        if (i === cleanData.length - 1 && i < inputs.length - 1) {
                            inputs[i + 1].focus();
                        } else if (i === inputs.length - 1) {
                            inputs[i].blur();
                            // Submit form after full paste
                            const form = codeInputContainer.closest('form');
                            if (form) {
                                setTimeout(() => {
                                    showVerificationAnimation();
                                    setTimeout(() => {
                                        form.submit();
                                    }, 1000);
                                }, 300);
                            }
                        }
                    }
                });
            });
        });
    }
}

// Show verification animation when code is complete
function showVerificationAnimation() {
    const codeForm = document.querySelector('.code-form');
    
    if (codeForm) {
        // Hide form
        codeForm.style.opacity = '0';
        setTimeout(() => {
            codeForm.style.display = 'none';
            
            // Create verification animation
            const verificationAnimation = document.createElement('div');
            verificationAnimation.className = 'verification-animation';
            verificationAnimation.innerHTML = `
                <div class="verification-circle">
                    <div class="verification-icon">✓</div>
                </div>
            `;
            
            // Insert animation before form
            codeForm.parentNode.insertBefore(verificationAnimation, codeForm);
        }, 300);
    }
}

// Setup password strength meter
function setupPasswordStrength() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        if (input.id === 'password' || input.id === 'new_password') {
            // Create strength indicator
            let strengthIndicator = document.getElementById('password-strength');
            if (!strengthIndicator) {
                strengthIndicator = document.createElement('div');
                strengthIndicator.id = 'password-strength';
                strengthIndicator.className = 'password-strength';
                input.parentNode.insertBefore(strengthIndicator, input.nextSibling);
            }
            
            input.addEventListener('input', function() {
                const password = this.value;
                const strength = calculatePasswordStrength(password);
                strengthIndicator.innerHTML = getPasswordStrengthText(strength);
                strengthIndicator.className = 'password-strength ' + getPasswordStrengthClass(strength);
                
                // Show/hide based on whether password field has value
                if (password.length > 0) {
                    strengthIndicator.style.display = 'inline-block';
                } else {
                    strengthIndicator.style.display = 'none';
                }
            });
            
            // Initially hide strength indicator
            strengthIndicator.style.display = 'none';
        }
    });
}

// Calculate password strength (0-5)
function calculatePasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Complexity check
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return Math.min(strength, 5);
}

// Get password strength text
function getPasswordStrengthText(strength) {
    const strengthTexts = [
        'חלש מאוד',
        'חלש',
        'בינוני',
        'חזק',
        'חזק מאוד',
        'חזק מאוד'
    ];
    return strengthTexts[strength];
}

// Get password strength CSS class
function getPasswordStrengthClass(strength) {
    const strengthClasses = [
        'very-weak',
        'weak',
        'medium',
        'strong',
        'very-strong',
        'very-strong'
    ];
    return strengthClasses[strength];
}