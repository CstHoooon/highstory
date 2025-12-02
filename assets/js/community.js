// 커뮤니티 관련 JavaScript

let isSubmitting = false; // 중복 제출 방지 플래그

document.addEventListener('DOMContentLoaded', function() {
    initializeCommunity();
});

function initializeCommunity() {
    // 모달 외부 클릭 시 닫기
    const modal = document.getElementById('writeModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeWriteModal();
            }
        });
    }
    
    // JavaScript 제출 이벤트 제거 - 일반 PHP 폼 제출 사용
    // 모바일 호환성을 위해 기본 HTML 폼 제출 방식 사용
}

function openWriteModal() {
    const modal = document.getElementById('writeModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // 제출 플래그 초기화
        isSubmitting = false;
    }
}

function closeWriteModal() {
    const modal = document.getElementById('writeModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // 폼 초기화
        document.getElementById('writeForm').reset();
        document.getElementById('anonymous').checked = true;
    }
}

// 기존 JavaScript 제출 방식 (사용 안 함 - 모바일 호환성 문제로 일반 폼 제출 사용)
/*
function handleWriteSubmit(e) {
    e.preventDefault();
    
    // 중복 제출 방지
    if (isSubmitting) {
        return false;
    }
    
    isSubmitting = true;
    
    const form = e.target;
    const submitBtn = form.querySelector('.btn-submit');
    
    if (!submitBtn) {
        isSubmitting = false;
        return false;
    }
    
    const originalText = submitBtn.innerHTML;
    
    // 로딩 상태
    submitBtn.innerHTML = '<span class="loading-spinner"></span>게시 중...';
    submitBtn.disabled = true;
    
    // 폼 데이터 수집
    const formData = new FormData(form);
    
    fetch('ajax/write_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed data:', data);
            
            if (data.success) {
                // 성공 시 페이지 새로고침
                showAlert('게시글이 작성되었습니다! 새로고침 중...', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                // 실패 시 에러 메시지 표시
                showAlert(data.message || '게시글 작성에 실패했습니다.', 'danger');
                console.error('Error details:', data.debug);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                isSubmitting = false;
            }
        } catch (e) {
            console.error('Parse error:', e);
            console.error('Raw response:', text);
            showAlert('응답 처리 오류: ' + e.message, 'danger');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            isSubmitting = false;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showAlert('서버 통신 오류: ' + error.message, 'danger');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        isSubmitting = false;
    });
    
    return false;
}
*/

function viewPost(postId) {
    // 게시글 상세 페이지로 이동 (향후 구현)
    window.location.href = `post.php?id=${postId}`;
}

function likePost(postId) {
    fetch('ajax/like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 좋아요 수 업데이트
            const likeElement = document.querySelector(`[onclick="likePost(${postId})"]`);
            if (likeElement) {
                const likeCount = likeElement.closest('.post-item').querySelector('.post-stats span:nth-child(2)');
                if (likeCount) {
                    likeCount.innerHTML = `<i class="fas fa-heart"></i> ${data.like_count}`;
                }
            }
        } else {
            showAlert(data.message || '좋아요 처리에 실패했습니다.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('오류가 발생했습니다.', 'danger');
    });
}

function showAlert(message, type = 'info') {
    // 기존 알림 제거
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '3000';
    alertDiv.style.minWidth = '300px';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // 3초 후 자동 숨김
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeWriteModal();
    }
});

// 무한 스크롤 (향후 구현)
let isLoading = false;
let currentPage = 1;

function loadMorePosts() {
    if (isLoading) return;
    
    isLoading = true;
    currentPage++;
    
    fetch(`ajax/load_posts.php?page=${currentPage}`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.posts.length > 0) {
            // 게시글 추가
            const postsContainer = document.querySelector('.posts-container');
            data.posts.forEach(post => {
                const postElement = createPostElement(post);
                postsContainer.appendChild(postElement);
            });
        }
        isLoading = false;
    })
    .catch(error => {
        console.error('Error:', error);
        isLoading = false;
    });
}

function createPostElement(post) {
    const postDiv = document.createElement('div');
    postDiv.className = 'post-item';
    postDiv.onclick = () => viewPost(post.id);
    
    const authorBadge = post.is_anonymous ? 
        '<span class="anonymous-badge">익명</span>' : 
        `<span class="real-name-badge">${post.real_name}</span>`;
    
    postDiv.innerHTML = `
        <div class="post-header">
            <h3 class="post-title">${post.title}</h3>
            <div class="post-meta">
                <div class="post-author">
                    ${authorBadge}
                </div>
                <div class="post-stats">
                    <span><i class="fas fa-eye"></i> ${post.view_count}</span>
                    <span><i class="fas fa-heart"></i> ${post.like_count}</span>
                    <span><i class="fas fa-comment"></i> ${post.comment_count}</span>
                </div>
            </div>
        </div>
        <div class="post-content">
            ${post.content.replace(/\n/g, '<br>')}
        </div>
        <div class="post-footer">
            <div class="post-date">
                <i class="fas fa-clock"></i>
                ${new Date(post.created_at).toLocaleString('ko-KR')}
            </div>
            <div class="post-actions">
                <a href="#" onclick="likePost(${post.id}); return false;">
                    <i class="fas fa-heart"></i> 좋아요
                </a>
                <a href="#" onclick="viewPost(${post.id}); return false;">
                    <i class="fas fa-comment"></i> 댓글
                </a>
            </div>
        </div>
    `;
    
    return postDiv;
}
