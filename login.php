<?php
require_once 'config/config.php';

$page_title = '로그인';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $school_id = $_POST['school_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($school_id) || empty($username) || empty($password)) {
        $error_message = "모든 항목을 입력해주세요.";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, s.name as school_name 
                FROM every_users u 
                JOIN every_schools s ON u.school_id = s.id 
                WHERE u.username = ? AND u.school_id = ?
            ");
            $stmt->execute([$username, $school_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['real_name'] = $user['real_name'];
                $_SESSION['school_id'] = $user['school_id'];
                $_SESSION['school_name'] = $user['school_name'];
                $_SESSION['student_number'] = $user['student_number'];
                
                header('Location: main.php');
                exit;
            } else {
                $error_message = "아이디 또는 비밀번호가 올바르지 않습니다.";
            }
        } catch(PDOException $e) {
            $error_message = "로그인 중 오류가 발생했습니다.";
        }
    }
}

try {
    $stmt = $pdo->query("SELECT id, name, code FROM every_schools ORDER BY code");
    $schools = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "학교 목록을 불러올 수 없습니다.";
}

include 'includes/header.php';
?>

<style>
@import url('assets/css/auth.css');
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h4><i class="fas fa-sign-in-alt me-2"></i>로그인</h4>
        </div>
        <div class="auth-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">학교 선택</label>
                        <select class="form-select" id="school_id" name="school_id" required>
                            <option value="">학교를 선택해주세요</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>" <?php echo (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($school['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">아이디</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required placeholder="아이디를 입력하세요">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">비밀번호</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required placeholder="비밀번호를 입력하세요">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-auth">
                            <i class="fas fa-sign-in-alt me-2"></i>로그인
                        </button>
                    </div>
                </form>
                
            <div class="text-center mt-3">
                <p class="mb-0">계정이 없으신가요? <a href="signup.php" class="auth-link">회원가입</a></p>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/login.js"></script>

<?php include 'includes/footer.php'; ?>
