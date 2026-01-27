<?php
// public/register.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/csrf.php';



$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Invalid CSRF token';
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $program = $_POST['program'] ?? null;
    $year = intval($_POST['year'] ?? 0);

    if ($username === '') $errors[] = 'Username is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    if ($full_name === '') $errors[] = 'Full name is required';
    if ($email === false) $errors[] = 'Valid email is required';
    if (!in_array($program, ['BSIT','BSCS','BSIS'], true)) $errors[] = 'Program is required';

    if (empty($errors)) {
        $pdo = DB::getConnection();
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, email, role, program, year) VALUES (:u, :p, :n, :e, 'student', :prog, :yr)");
            $ins->execute([
                ':u' => $username,
                ':p' => $password_hash,
                ':n' => $full_name,
                ':e' => $email,
                ':prog' => $program,
                ':yr' => $year
            ]);
            $userId = (int)$pdo->lastInsertId();
            // Auto-login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = 'student';
            session_regenerate_id(true);
            $success = true;
            header('Location: /capstone-repo/student/student_dashboard.php');
            exit;
        }
    }
}

// Render minimal HTML form (or include your template)
$csrf = csrf_token();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title>
<link href="/capstone-repo/assets/custom.css" rel="stylesheet">
</head>
<body>
<main class="container-small py-3">
  <div class="card mt-6">
    <div class="card-header">
      <h1 class="text-center mb-0">Create Account</h1>
    </div>
    <div class="card-body">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
      <?php endif; ?>
      <form method="post" action="register.php">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <p class="text-muted mb-0" style="font-size: 0.875rem;">Min. 8 characters</p>
        </div>
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="program">Program</label>
          <select id="program" name="program" required>
            <option value="">Select Program</option>
            <option value="BSIT">BSIT</option>
            <option value="BSCS">BSCS</option>
            <option value="BSIS">BSIS</option>
          </select>
        </div>
        <div class="form-group">
          <label for="year">Year</label>
          <input type="number" id="year" name="year" min="2000" max="2100">
        </div>
        <button type="submit" class="btn-primary btn-block">Create Account</button>
      </form>
    </div>
    <div class="card-footer text-center">
      <p class="mb-0">Already have an account? <a href="login.php">Sign in here</a></p>
    </div>
  </div>
</main>
<footer>
  <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>
<script src="/capstone-repo/assets/app.js"></script>
</body>