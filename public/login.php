<?php
// public/login.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/csrf.php';



$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!validate_csrf($token)) {
        $error = 'Invalid CSRF token';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username and password required';
        } else {
            $pdo = DB::getConnection();
            $stmt = $pdo->prepare("SELECT user_id, password_hash, role, status FROM users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
                // Successful login
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['role'] = $user['role'];
                session_regenerate_id(true);
                // Redirect by role
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /capstone-repo/admin/admin_dashboard.php');
                        break;
                    case 'faculty':
                        header('Location: /capstone-repo/faculty/faculty_dashboard.php');
                        break;
                    default:
                        header('Location: /capstone-repo/student/student_dashboard.php');
                        break;
                }
                exit;
            } else {
                $error = 'Invalid credentials or inactive account';
            }
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
  <html>
  
<head><meta charset="utf-8"><title>Login</title>
<link href="/capstone-repo/assets/custom.css" rel="stylesheet">
</head>
<body>
<main class="container-small py-3">
  <div class="card mt-6">
    <div class="card-header">
      <h1 class="text-center mb-0">Welcome Back</h1>
    </div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <form method="post" action="login.php">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-primary btn-block">Sign In</button>
      </form>
    </div>
    <div class="card-footer text-center">
      <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
  </div>
</main>
<footer>
  <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>
<script src="/capstone-repo/assets/app.js"></script>
</body>
</html>