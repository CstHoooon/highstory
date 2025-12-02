<?php
require_once 'config/config.php';

$page_title = '홈';

$popular_posts = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.real_name, 
                   (SELECT COUNT(*) FROM every_comments WHERE post_id = p.id) as comment_count
            FROM every_posts p
            JOIN every_users u ON p.user_id = u.id
            WHERE p.school_id = ? AND p.like_count >= 10
            ORDER BY p.like_count DESC, p.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$_SESSION['school_id']]);
        $popular_posts = $stmt->fetchAll();
    } catch(PDOException $e) {
        $popular_posts = [];
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="jumbotron bg-primary text-white p-5 rounded mb-4">
            <h1 class="display-4"><?php echo SITE_NAME; ?>에 오신 것을 환영합니다!</h1>
            <p class="lead">고등학생을 위한 시간표, 급식표, 학교 커뮤니티 서비스입니다.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a class="btn btn-light btn-lg" href="signup.php" role="button">지금 시작하기</a>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">시간표</h5>
                        <p class="card-text">학년별 주간 시간표를 확인하세요.</p>
                        <a href="timetable.php" class="btn btn-primary">시간표 보기</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">급식표</h5>
                        <p class="card-text">이번 달 급식 메뉴를 확인하세요.</p>
                        <a href="meal.php" class="btn btn-warning">급식표 보기</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-comments fa-3x text-info mb-3"></i>
                        <h5 class="card-title">커뮤니티</h5>
                        <p class="card-text">학교 친구들과 소통하고 정보를 공유하세요.</p>
                        <a href="community.php" class="btn btn-info">커뮤니티</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">내 정보</h5>
                </div>
                <div class="card-body">
                    <p><strong>이름:</strong> <?php echo htmlspecialchars($_SESSION['real_name']); ?></p>
                    <p><strong>학교:</strong> <?php echo htmlspecialchars($_SESSION['school_name']); ?></p>
                    <p><strong>학번:</strong> <?php echo htmlspecialchars($_SESSION['student_number']); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">로그인</h5>
                </div>
                <div class="card-body">
                    <p>로그인하여 모든 기능을 이용해보세요!</p>
                    <a href="login.php" class="btn btn-primary w-100">로그인</a>
                    <a href="signup.php" class="btn btn-outline-primary w-100 mt-2">회원가입</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-gradient-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-fire me-2"></i>인기 게시글
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-lock mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">로그인하시면<br>인기글을 확인할 수 있습니다.</p>
                    </div>
                <?php elseif (empty($popular_posts)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-fire mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mb-0">아직 인기글이 없습니다.</p>
                        <small>좋아요 10개 이상인 글이 표시됩니다.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular_posts as $index => $post): ?>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex align-items-start">
                                    <div class="popular-rank me-2">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1 popular-post-title">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </h6>
                                        </div>
                                        <div class="popular-post-stats">
                                            <?php if ($post['is_anonymous']): ?>
                                                <span class="badge bg-secondary">익명</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($post['real_name']); ?></span>
                                            <?php endif; ?>
                                            <small class="text-muted ms-2">
                                                <i class="fas fa-heart text-danger"></i> <?php echo $post['like_count']; ?>
                                                <i class="fas fa-comment ms-2"></i> <?php echo $post['comment_count']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="community.php" class="text-decoration-none">
                            더 많은 글 보기 <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-danger {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
}

.popular-rank {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.popular-post-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
}

.popular-post-stats {
    display: flex;
    align-items: center;
    margin-top: 5px;
}

.popular-post-stats .badge {
    font-size: 0.7rem;
    padding: 2px 6px;
}

.list-group-item-action:hover {
    background-color: #f8f9fa;
}

.list-group-item-action:hover .popular-post-title {
    color: #ff6b6b;
}

.card-footer {
    background-color: #f8f9fa;
    padding: 10px;
}

.card-footer a {
    color: #ff6b6b;
    font-weight: 600;
    font-size: 0.9rem;
}

.card-footer a:hover {
    color: #ff5252;
}
</style>

<?php include 'includes/footer.php'; ?>
