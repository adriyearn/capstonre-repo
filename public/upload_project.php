<?php
// public/upload_project.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

require_role('student');

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Invalid CSRF token';
    }

    $title = trim($_POST['title'] ?? '');
    $abstract = trim($_POST['abstract'] ?? '');
    $program = $_POST['program'] ?? '';
    $year_completed = intval($_POST['year_completed'] ?? 0);
    $keywords = trim($_POST['keywords'] ?? '');
    $adviser = trim($_POST['adviser'] ?? '');

    if ($title === '') $errors[] = 'Title is required';
    if (!in_array($program, ['BSIT','BSCS','BSIS'], true)) $errors[] = 'Program is required';
    if ($year_completed < 2000 || $year_completed > 2100) $errors[] = 'Enter a valid year';

    if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Project file is required';
    }

    if (empty($errors)) {
        $file = $_FILES['project_file'];
        $maxSize = 15 * 1024 * 1024; // 15 MB
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name'] ?? '');

        if ($mime !== 'application/pdf') {
            $errors[] = 'Only PDF files are allowed';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'File exceeds maximum size of 15 MB';
        } else {
            // Prepare upload directory
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Randomized filename
            $safeName = bin2hex(random_bytes(16)) . '.pdf';
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = 'Failed to move uploaded file';
            } else {
                // Insert project record and audit log inside transaction
                $pdo = DB::getConnection();
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("INSERT INTO projects (title, abstract, program, year_completed, keywords, file_path, file_name_original, file_size, uploader_id, adviser, status) VALUES (:title, :abstract, :program, :year, :keywords, :file_path, :orig_name, :file_size, :uploader, :adviser, 'submitted')");
                    $stmt->execute([
                        ':title' => $title,
                        ':abstract' => $abstract,
                        ':program' => $program,
                        ':year' => $year_completed,
                        ':keywords' => $keywords,
                        ':file_path' => $safeName,
                        ':orig_name' => $file['name'],
                        ':file_size' => $file['size'],
                        ':uploader' => current_user_id(),
                        ':adviser' => $adviser
                    ]);
                    $projectId = (int)$pdo->lastInsertId();

                    $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, target_type, target_id, details) VALUES (:uid, 'upload', 'project', :pid, :details)");
                    $log->execute([
                        ':uid' => current_user_id(),
                        ':pid' => $projectId,
                        ':details' => 'Uploaded file: ' . $file['name']
                    ]);

                    $pdo->commit();
                    $success = true;

                    // Optionally notify admin/faculty here (email or in-app)
                    header('Location: /capstone-repo/student/student_dashboard.php');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log('Upload error: ' . $e->getMessage());
                    $errors[] = 'Server error while saving project';
                    // Remove file if DB failed
                    if (is_file($destination)) unlink($destination);
                }
            }
        }
    }
}

// If reached here, show errors or form (frontend templates handle this)
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Upload Project</title>
<link href="/capstone-repo/assets/custom.css" rel="stylesheet">
</head>
<body>
<header>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-brand">Capstone Portal</div>
      <a href="/capstone-repo/student/student_dashboard.php" class="btn btn-ghost btn-sm">Back to Dashboard</a>
    </nav>
  </div>
</header>
<main class="container-small py-3">
  <div class="card">
    <div class="card-header">
      <h1 class="mb-0">Upload New Project</h1>
    </div>
    <div class="card-body">
      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
      <?php endif; ?>
      <form method="post" action="upload_project.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="form-group">
          <label for="title">Project Title *</label>
          <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
          <label for="abstract">Abstract *</label>
          <textarea id="abstract" name="abstract" required></textarea>
        </div>
        <div class="grid grid-cols-2">
          <div class="form-group">
            <label for="program">Program *</label>
            <select id="program" name="program" required>
              <option value="">Select Program</option>
              <option value="BSIT">BSIT</option>
              <option value="BSCS">BSCS</option>
              <option value="BSIS">BSIS</option>
            </select>
          </div>
          <div class="form-group">
            <label for="year_completed">Year Completed *</label>
            <input type="number" id="year_completed" name="year_completed" min="2000" max="2100" required>
          </div>
        </div>
        <div class="form-group">
          <label for="keywords">Keywords</label>
          <input type="text" id="keywords" name="keywords" placeholder="Comma-separated keywords">
        </div>
        <div class="form-group">
          <label for="adviser">Adviser</label>
          <input type="text" id="adviser" name="adviser">
        </div>
        <div class="form-group">
          <label for="project_file">Project PDF (Max 15 MB) *</label>
          <input type="file" id="project_file" name="project_file" accept="application/pdf" required>
          <p class="text-muted mb-0" style="font-size: 0.875rem;">Only PDF files are accepted</p>
        </div>
        <button type="submit" class="btn-primary btn-block">Upload Project</button>
      </form>
    </div>
  </div>
</main>
<footer>
  <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>
<script src="/capstone-repo/assets/app.js"></script>
</body>
</body>
</html>