<?php
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = '커뮤니티';
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['write_submit'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? (int)$_POST['is_anonymous'] : 1;
    
    if (empty($title) || empty($content)) {
        $error_message = '제목과 내용을 모두 입력해주세요.';
    } else if (strlen($title) > 200) {
        $error_message = '제목은 200자 이하로 입력해주세요.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO every_posts (user_id, school_id, title, content, is_anonymous, view_count, like_count) 
                VALUES (?, ?, ?, ?, ?, 0, 0)
            ");
            
            $result = $stmt->execute([
                $_SESSION['user_id'], 
                $_SESSION['school_id'], 
                $title, 
                $content, 
                $is_anonymous
            ]);
            
            if ($result) {
                $success_message = '게시글이 성공적으로 작성되었습니다!';
                header('Location: community.php?success=1');
                exit;
            } else {
                $error_message = '게시글 작성에 실패했습니다.';
            }
        } catch(PDOException $e) {
            $error_message = '게시글 작성 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = '게시글이 성공적으로 작성되었습니다!';
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM every_posts WHERE school_id = ?");
    $stmt->execute([$_SESSION['school_id']]);
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $per_page);
} catch(PDOException $e) {
    $total_posts = 0;
    $total_pages = 1;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.real_name, u.username, s.name as school_name,
               (SELECT COUNT(*) FROM every_comments WHERE post_id = p.id) as comment_count
        FROM every_posts p
        JOIN every_users u ON p.user_id = u.id
        JOIN every_schools s ON p.school_id = s.id
        WHERE p.school_id = ?
        ORDER BY p.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ");
    $stmt->execute([$_SESSION['school_id']]);
    $posts = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "게시글을 불러올 수 없습니다: " . $e->getMessage();
    $posts = [];
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM every_posts WHERE school_id = ? AND like_count >= 10");
    $stmt->execute([$_SESSION['school_id']]);
    $total_popular_posts = $stmt->fetchColumn();
    $total_popular_pages = ceil($total_popular_posts / $per_page);
} catch(PDOException $e) {
    $total_popular_posts = 0;
    $total_popular_pages = 1;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.real_name, u.username, s.name as school_name,
               (SELECT COUNT(*) FROM every_comments WHERE post_id = p.id) as comment_count
        FROM every_posts p
        JOIN every_users u ON p.user_id = u.id
        JOIN every_schools s ON p.school_id = s.id
        WHERE p.school_id = ? AND p.like_count >= 10
        ORDER BY p.like_count DESC, p.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ");
    $stmt->execute([$_SESSION['school_id']]);
    $popular_posts = $stmt->fetchAll();
} catch(PDOException $e) {
    $popular_posts = [];
}

try {
    $stmt = $pdo->prepare("SELECT name FROM every_schools WHERE id = ?");
    $stmt->execute([$_SESSION['school_id']]);
    $school = $stmt->fetch();
} catch(PDOException $e) {
    $school = ['name' => '알 수 없는 학교'];
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/community.css?v=<?php echo time(); ?>">

<div class="community-container">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="community-header">
        <h1><i class="fas fa-comments me-3"></i><?php echo htmlspecialchars($school['name']); ?> 커뮤니티</h1>
        <p>우리 학교 친구들과 소통하고 정보를 공유해보세요</p>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?>        </div>
    <?php endif; ?>

    <button class="write-btn" onclick="openWriteModal()">
        <i class="fas fa-pen"></i>
        <span>글쓰기</span>
    </button>

    <div class="tab-menu">
        <button class="tab-btn active" data-tab="all">
            <i class="fas fa-list me-1"></i>전체 글
            <span class="badge"><?php echo $total_posts; ?></span>
        </button>
        <button class="tab-btn" data-tab="popular">
            <i class="fas fa-fire me-1"></i>인기 글
            <span class="badge"><?php echo $total_popular_posts; ?></span>
        </button>
    </div>

    <div class="posts-container" id="allPosts">
        <?php if (empty($posts)): ?>
            <div class="text-center py-5">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">아직 게시글이 없습니다</h4>
                <p class="text-muted">첫 번째 게시글을 작성해보세요!</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-item-compact" onclick="viewPost(<?php echo $post['id']; ?>)">
                    <div class="post-main">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <div class="post-info">
                            <?php if ($post['is_anonymous']): ?>
                                <span class="anonymous-badge">익명</span>
                            <?php else: ?>
                                <span class="real-name-badge"><?php echo htmlspecialchars($post['real_name']); ?></span>
                            <?php endif; ?>
                            <span class="post-date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('m-d H:i', strtotime($post['created_at'])); ?>
                            </span>
                            <span class="post-stats">
                                <i class="fas fa-eye"></i> <?php echo $post['view_count']; ?>
                                <i class="fas fa-heart"></i> <?php echo $post['like_count']; ?>
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="posts-container" id="popularPosts" style="display: none;">
        <?php if (empty($popular_posts)): ?>
            <div class="text-center py-5">
                <i class="fas fa-fire fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">아직 인기글이 없습니다</h4>
                <p class="text-muted">좋아요 10개 이상인 글이 인기글로 표시됩니다!</p>
            </div>
        <?php else: ?>
            <?php foreach ($popular_posts as $post): ?>
                <div class="post-item-compact popular-post" onclick="viewPost(<?php echo $post['id']; ?>)">
                    <div class="popular-badge">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="post-main">
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <div class="post-info">
                            <?php if ($post['is_anonymous']): ?>
                                <span class="anonymous-badge">익명</span>
                            <?php else: ?>
                                <span class="real-name-badge"><?php echo htmlspecialchars($post['real_name']); ?></span>
                            <?php endif; ?>
                            <span class="post-date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('m-d H:i', strtotime($post['created_at'])); ?>
                            </span>
                            <span class="post-stats">
                                <i class="fas fa-eye"></i> <?php echo $post['view_count']; ?>
                                <i class="fas fa-heart popular-likes"></i> <?php echo $post['like_count']; ?>
                                <i class="fas fa-comment"></i> <?php echo $post['comment_count']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($total_popular_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_popular_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_popular_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-btn">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div id="writeModal" class="write-modal">
    <div class="write-modal-content">
        <div class="write-modal-header">
            <h3><i class="fas fa-pen me-2"></i>글쓰기</h3>
            <button class="close-btn" onclick="closeWriteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="writeForm" method="POST" action="community.php">
            <div class="form-group">
                <label for="post_title">제목</label>
                <input type="text" class="form-control" id="post_title" name="title" required placeholder="제목을 입력하세요">
            </div>
            
            <div class="form-group">
                <label for="post_content">내용</label>
                <textarea class="form-control" id="post_content" name="content" required placeholder="내용을 입력하세요"></textarea>
            </div>
            
            <div class="form-group">
                <label>작성자 표시</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input type="radio" id="anonymous" name="is_anonymous" value="1" checked>
                        <label for="anonymous">익명으로 작성</label>
                    </div>
                    <div class="radio-item">
                        <input type="radio" id="real_name" name="is_anonymous" value="0">
                        <label for="real_name">실명으로 작성</label>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="write_submit" class="btn-submit">
                <i class="fas fa-paper-plane me-2"></i>게시하기
            </button>
        </form>
    </div>
</div>

<script src="assets/js/community.js"></script>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const tab = this.dataset.tab;
        if (tab === 'all') {
            document.getElementById('allPosts').style.display = 'block';
            document.getElementById('popularPosts').style.display = 'none';
        } else if (tab === 'popular') {
            document.getElementById('allPosts').style.display = 'none';
            document.getElementById('popularPosts').style.display = 'block';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
