<?php
require_once 'config/config.php';

$page_title = '회원가입';
$error_message = '';
$success_message = '';

try {
    $stmt = $pdo->query("SELECT id, name, code FROM every_schools ORDER BY code");
    $schools = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "학교 목록을 불러올 수 없습니다.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $school_id = $_POST['school_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $student_number = trim($_POST['student_number'] ?? '');
    $real_name = trim($_POST['real_name'] ?? '');
    
    if (empty($school_id) || empty($username) || empty($password) || empty($student_number) || empty($real_name)) {
        $error_message = "모든 필수 항목을 입력해주세요.";
    } elseif ($password !== $confirm_password) {
        $error_message = "비밀번호가 일치하지 않습니다.";
    } elseif (strlen($password) < 6) {
        $error_message = "비밀번호는 6자 이상이어야 합니다.";
    } elseif (strlen($username) < 3) {
        $error_message = "아이디는 3자 이상이어야 합니다.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM every_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = "이미 사용 중인 아이디입니다.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO every_users (school_id, username, password, student_number, real_name) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $username, $hashed_password, $student_number, $real_name]);
                
                $success_message = "회원가입이 완료되었습니다! 로그인해주세요.";
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e) {
            $error_message = "회원가입 중 오류가 발생했습니다.";
        }
    }
}

include 'includes/header.php';
?>

<style>
@import url('assets/css/auth.css');
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h4><i class="fas fa-user-plus me-2"></i>회원가입</h4>
        </div>
        <div class="auth-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">학교 선택 <span class="text-danger">*</span></label>
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
                        <label for="username" class="form-label">아이디 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               required minlength="3" placeholder="3자 이상 입력">
                        <div class="form-text">영문, 숫자만 사용 가능합니다.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">비밀번호 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required minlength="6" placeholder="6자 이상 입력">
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">비밀번호 확인 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               required placeholder="비밀번호를 다시 입력">
                    </div>
                    
                    <div class="mb-3">
                        <label for="student_number" class="form-label">학번 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="student_number" name="student_number" 
                               value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>" 
                               required placeholder="학번 5자리 입력">
                    </div>
                    
                    <div class="mb-3">
                        <label for="real_name" class="form-label">이름 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="real_name" name="real_name" 
                               value="<?php echo htmlspecialchars($_POST['real_name'] ?? ''); ?>" 
                               required placeholder="실명을 입력해주세요">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-auth">
                            <i class="fas fa-user-plus me-2"></i>회원가입
                        </button>
                    </div>
                </form>
                
            <div class="text-center mt-3">
                <p class="mb-0">이미 계정이 있으신가요? <a href="login.php" class="auth-link">로그인</a></p>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/auth.js"></script>

<?php include 'includes/footer.php'; ?>
