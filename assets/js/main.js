document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    initializeTimetable();
    initializeGradeCalculator();
    initializeCommunity();
});

function initializeApp() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
}

function initializeTimetable() {
    const colorInputs = document.querySelectorAll('.color-picker');
    colorInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const subjectCard = this.closest('.timetable-subject');
            if (subjectCard) {
                subjectCard.style.backgroundColor = this.value;
            }
        });
    });
}

function initializeGradeCalculator() {
    const gradeInputs = document.querySelectorAll('.grade-input input');
    gradeInputs.forEach(function(input) {
        input.addEventListener('input', calculateGrade);
    });
}

function calculateGrade() {
    const midterm = parseFloat(document.getElementById('midterm')?.value) || 0;
    const final = parseFloat(document.getElementById('final')?.value) || 0;
    const assignment = parseFloat(document.getElementById('assignment')?.value) || 0;
    const attendance = parseFloat(document.getElementById('attendance')?.value) || 0;
    
    const total = (midterm * 0.3) + (final * 0.4) + (assignment * 0.2) + (attendance * 0.1);
    
    const resultElement = document.getElementById('grade-result');
    if (resultElement) {
        resultElement.textContent = total.toFixed(2) + '점';
        
        // 등급 표시
        let grade = '';
        if (total >= 90) grade = 'A+';
        else if (total >= 85) grade = 'A';
        else if (total >= 80) grade = 'B+';
        else if (total >= 75) grade = 'B';
        else if (total >= 70) grade = 'C+';
        else if (total >= 65) grade = 'C';
        else if (total >= 60) grade = 'D+';
        else if (total >= 55) grade = 'D';
        else grade = 'F';
        
        const gradeElement = document.getElementById('grade-letter');
        if (gradeElement) {
            gradeElement.textContent = grade;
        }
    }
}

// 커뮤니티 관련 기능
function initializeCommunity() {
    // 게시글 좋아요 기능
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            toggleLike(this);
        });
    });
    
    // 댓글 폼 토글
    const commentToggles = document.querySelectorAll('.comment-toggle');
    commentToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const commentForm = this.nextElementSibling;
            if (commentForm) {
                commentForm.style.display = commentForm.style.display === 'none' ? 'block' : 'none';
            }
        });
    });
}

function toggleLike(button) {
    const postId = button.dataset.postId;
    const isLiked = button.classList.contains('liked');
    
    // AJAX 요청으로 좋아요 토글
    fetch('ajax/toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            action: isLiked ? 'unlike' : 'like'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeCount = button.querySelector('.like-count');
            if (likeCount) {
                likeCount.textContent = data.like_count;
            }
            
            if (isLiked) {
                button.classList.remove('liked');
                button.innerHTML = '<i class="far fa-heart"></i>';
            } else {
                button.classList.add('liked');
                button.innerHTML = '<i class="fas fa-heart"></i>';
            }
        }
    })
    .catch(error => {
        showAlert('오류가 발생했습니다.', 'danger');
    });
}

// 유틸리티 함수들
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // 5초 후 자동 숨김
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return '어제';
    } else if (diffDays < 7) {
        return `${diffDays}일 전`;
    } else {
        return date.toLocaleDateString('ko-KR');
    }
}

// 폼 유효성 검사
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

// 파일 업로드 미리보기
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
