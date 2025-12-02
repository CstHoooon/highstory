<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = '게시글';
$error_message = '';

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header('Location: community.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.real_name, u.username, s.name as school_name,
               (SELECT COUNT(*) FROM every_comments WHERE post_id = p.id) as comment_count
        FROM every_posts p
        JOIN every_users u ON p.user_id = u.id
        JOIN every_schools s ON p.school_id = s.id
        WHERE p.id = ? AND p.school_id = ?
    ");
    $stmt->execute([$post_id, $_SESSION['school_id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        $error_message = "존재하지 않거나 접근할 수 없는 게시글입니다.";
    } else {
        $stmt = $pdo->prepare("UPDATE every_posts SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    }
} catch(PDOException $e) {
    $error_message = "게시글을 불러올 수 없습니다: " . $e->getMessage();
}

$comments = [];
if ($post) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.real_name, u.username
            FROM every_comments c
            JOIN every_users u ON c.user_id = u.id
            WHERE c.post_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();
    } catch(PDOException $e) {
    }
}

include 'includes/header.php';
?>

<style>
@import url('assets/css/community.css');

.post-detail-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.post-detail {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
}

.post-detail-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.post-detail-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
}

.post-detail-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #6c757d;
    font-size: 0.9rem;
}

.post-detail-content {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
    margin: 30px 0;
    min-height: 200px;
    white-space: pre-wrap;
    word-break: break-word;
}

.post-detail-footer {
    border-top: 1px solid #e9ecef;
    padding-top: 20px;
    display: flex;
    gap: 15px;
}

.post-action-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-like {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-like:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(245,87,108,0.3);
}

.comments-section {
    background: white;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
}

.comments-title {
    font-size: 1.3rem;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
}

.comment-item {
    border-bottom: 1px solid #e9ecef;
    padding: 15px 0;
}

.comment-item:last-child {
    border-bottom: none;
}

.comment-author {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.comment-content {
    color: #666;
    line-height: 1.6;
    margin-bottom: 5px;
}

.comment-date {
    font-size: 0.85rem;
    color: #6c757d;
}

.comment-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #e9ecef;
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #5a6268;
    color: white;
    transform: translateY(-2px);
}
</style>

<div class="post-detail-container">
    <a href="community.php" class="back-btn">
        <i class="fas fa-arrow-left me-2"></i>목록으로
    </a>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <a href="community.php" class="btn btn-primary">커뮤니티로 돌아가기</a>
    <?php elseif ($post): ?>
        <div class="post-detail">
            <div class="post-detail-header">
                <h1 class="post-detail-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-detail-meta">
                    <?php if ($post['is_anonymous']): ?>
                        <span class="anonymous-badge">익명</span>
                    <?php else: ?>
                        <span class="real-name-badge"><?php echo htmlspecialchars($post['real_name']); ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
                    <span><i class="fas fa-eye"></i> <?php echo $post['view_count']; ?></span>
                    <span><i class="fas fa-heart"></i> <?php echo $post['like_count']; ?></span>
                    <span><i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?></span>
                </div>
            </div>
            
            <div class="post-detail-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
            
            <div class="post-detail-footer">
                <button class="post-action-btn btn-like" onclick="likePost(<?php echo $post['id']; ?>)">
                    <i class="fas fa-heart me-2"></i>좋아요 (<?php echo $post['like_count']; ?>)
                </button>
            </div>
        </div>
        
        <div class="comments-section">
            <h3 class="comments-title">
                <i class="fas fa-comments me-2"></i>댓글 (<?php echo count($comments); ?>)
            </h3>
            
            <?php if (empty($comments)): ?>
                <p class="text-muted">첫 댓글을 작성해보세요!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-author">
                            <?php if ($comment['is_anonymous']): ?>
                                <span class="anonymous-badge">익명</span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($comment['real_name']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                        <div class="comment-date">
                            <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="comment-form">
                <h4>댓글 작성</h4>
                <form method="POST" action="ajax/write_comment.php">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <div class="form-group mb-3">
                        <textarea class="form-control" name="content" rows="3" required placeholder="댓글을 입력하세요"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="comment_anonymous" name="is_anonymous" value="1" checked>
                                <label for="comment_anonymous">익명으로 작성</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="comment_real_name" name="is_anonymous" value="0">
                                <label for="comment_real_name">실명으로 작성</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>댓글 작성
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/community.js"></script>

<?php include 'includes/footer.php'; ?>
