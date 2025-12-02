// 인증 관련 JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeAuth();
});

function initializeAuth() {
    // 현재 페이지 URL로 회원가입 페이지인지 확인
    const isSignupPage = window.location.pathname.includes('signup.php');
    
    if (isSignupPage) {
        // 회원가입 페이지에서만 실행할 검증들
        const confirmPasswordField = document.getElementById('confirm_password');
        const passwordField = document.getElementById('password');
        
        if (confirmPasswordField && passwordField) {
            confirmPasswordField.addEventListener('input', validatePasswordMatch);
            passwordField.addEventListener('input', validatePasswordMatch);
        }
        
        // 아이디 중복 확인 (회원가입에서만)
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.addEventListener('blur', checkUsernameAvailability);
        }
    }
    
    // 폼 제출 시 로딩 상태 (모든 페이지에서)
    const authForms = document.querySelectorAll('form');
    authForms.forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (confirmPassword && password !== confirmPassword) {
        document.getElementById('confirm_password').setCustomValidity('비밀번호가 일치하지 않습니다.');
    } else {
        document.getElementById('confirm_password').setCustomValidity('');
    }
}

function checkUsernameAvailability() {
    const username = this.value.trim();
    
    if (username.length >= 3) {
        // 로딩 상태 표시
        const originalValue = this.value;
        this.value = originalValue + ' (확인중...)';
        this.disabled = true;
        
        fetch('ajax/check_username.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'username=' + encodeURIComponent(username)
        })
        .then(response => response.json())
        .then(data => {
            this.value = originalValue;
            this.disabled = false;
            
            if (data.exists) {
                this.setCustomValidity('이미 사용 중인 아이디입니다.');
                showFieldError(this, '이미 사용 중인 아이디입니다.');
            } else {
                this.setCustomValidity('');
                clearFieldError(this);
            }
        })
        .catch(error => {
            this.value = originalValue;
            this.disabled = false;
        });
    }
}

function handleFormSubmit(event) {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (submitBtn) {
        // 로딩 상태로 변경
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loading-spinner"></span>처리중...';
        submitBtn.disabled = true;
        
        // 3초 후 원래 상태로 복원 (실제로는 페이지 이동이나 리다이렉트가 있을 것)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    }
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-danger mt-1';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
    field.classList.add('is-invalid');
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.classList.remove('is-invalid');
}

// 실시간 유효성 검사
function setupRealTimeValidation() {
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
}

// 비밀번호 강도 표시
function setupPasswordStrength() {
    const passwordField = document.getElementById('password');
    if (!passwordField) return;
    
    const strengthIndicator = document.createElement('div');
    strengthIndicator.className = 'password-strength mt-2';
    strengthIndicator.innerHTML = `
        <div class="strength-bar">
            <div class="strength-fill"></div>
        </div>
        <small class="strength-text text-muted"></small>
    `;
    
    passwordField.parentNode.appendChild(strengthIndicator);
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strengthIndicator, strength);
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 6) score += 1;
    if (password.length >= 8) score += 1;
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    
    return Math.min(score, 5);
}

function updatePasswordStrength(indicator, strength) {
    const fill = indicator.querySelector('.strength-fill');
    const text = indicator.querySelector('.strength-text');
    
    const percentages = [0, 20, 40, 60, 80, 100];
    const colors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#198754'];
    const texts = ['매우 약함', '약함', '보통', '강함', '매우 강함'];
    
    fill.style.width = percentages[strength] + '%';
    fill.style.backgroundColor = colors[strength - 1] || '#dc3545';
    text.textContent = strength > 0 ? texts[strength - 1] : '';
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    setupRealTimeValidation();
    setupPasswordStrength();
});
