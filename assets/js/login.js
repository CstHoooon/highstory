// 로그인 페이지 전용 JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeLogin();
});

function initializeLogin() {
    // 폼 제출 시 로딩 상태만 적용
    const loginForm = document.querySelector('form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleFormSubmit);
    }
}

function handleFormSubmit(event) {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (submitBtn) {
        // 로딩 상태로 변경
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="loading-spinner"></span>로그인 중...';
        submitBtn.disabled = true;
        
        // 3초 후 원래 상태로 복원 (실제로는 페이지 이동이나 리다이렉트가 있을 것)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    }
}

// 로딩 애니메이션
const loadingSpinner = `
    <style>
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s ease-in-out infinite;
        margin-right: 10px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
`;

// 스타일 추가
document.head.insertAdjacentHTML('beforeend', loadingSpinner);
