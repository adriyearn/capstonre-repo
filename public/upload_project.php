<?php
// public/upload_project.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';

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

                    // Notify faculty about new submission
                    $facultyStmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'faculty'");
                    $facultyStmt->execute();
                    $facultyUsers = $facultyStmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($facultyUsers)) {
                        create_bulk_notifications(
                            $facultyUsers,
                            'New Project Submission',
                            'A student has submitted a new capstone project for review.',
                            '/capstone-repo/faculty/faculty_dashboard.php',
                            'submission'
                        );
                    }

                    // Notify admin about new submission
                    $adminStmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'admin'");
                    $adminStmt->execute();
                    $adminUsers = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($adminUsers)) {
                        create_bulk_notifications(
                            $adminUsers,
                            'New Project Submitted',
                            'A student has submitted a capstone project.',
                            '/capstone-repo/admin/admin_dashboard.php',
                            'submission'
                        );
                    }

                    // Send emails to faculty and admin
                    $student_name = $_SESSION['username'] ?? 'A student';
                    
                    // Get current user info for email
                    $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = :uid");
                    $userStmt->execute([':uid' => current_user_id()]);
                    $user = $userStmt->fetch();
                    if ($user && !empty($user['full_name'])) {
                        $student_name = $user['full_name'];
                    }
                    
                    // Send emails to all faculty
                    if (!empty($facultyUsers)) {
                        foreach ($facultyUsers as $fid) {
                            $emailStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :uid");
                            $emailStmt->execute([':uid' => $fid]);
                            $emailRow = $emailStmt->fetch();
                            if ($emailRow && !empty($emailRow['email'])) {
                                send_project_submission_email($emailRow['email'], $student_name, $title);
                            }
                        }
                    }
                    
                    // Send emails to all admins
                    if (!empty($adminUsers)) {
                        foreach ($adminUsers as $aid) {
                            $emailStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :uid");
                            $emailStmt->execute([':uid' => $aid]);
                            $emailRow = $emailStmt->fetch();
                            if ($emailRow && !empty($emailRow['email'])) {
                                send_admin_notification_email(
                                    $emailRow['email'],
                                    "New Project Submission: $title",
                                    "A student ($student_name) has submitted a capstone project for review.\n\nProject Title: $title",
                                    'View Project',
                                    'http://localhost/capstone-repo/admin/admin_dashboard.php'
                                );
                            }
                        }
                    }

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Project - Capstone Portal</title>
    <link href="../assets/custom.css" rel="stylesheet">
    <link href="../assets/upload_project.css" rel="stylesheet">
    <link href="../assets/notifications.css" rel="stylesheet">
</head>
<body>
<header>
    <div class="navbar">
        <div class="navbar-brand">📚 Capstone Portal</div>
        <nav class="navbar-nav">
            <a href="../student/student_dashboard.php">← Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="upload-section">
        <h1>📤 Upload New Project</h1>
        <p>Submit your capstone project for review</p>
    </div>

    <div class="form-container">
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>Success!</strong> Your project has been uploaded successfully. Redirecting to dashboard...
                <script>setTimeout(() => window.location.href = '../student/student_dashboard.php', 2000);</script>
            </div>
        <?php else: ?>
            <form method="post" action="upload_project.php" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                
                <div class="form-section">
                    <h2>Project Information</h2>
                    
                    <div class="form-group">
                        <label for="title">Project Title *</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            placeholder="Enter your project title"
                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                            required
                            class="form-input"
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="program">Program *</label>
                            <select id="program" name="program" required class="form-select">
                                <option value="">Select Program</option>
                                <option value="BSIT" <?php echo ($_POST['program'] ?? '') === 'BSIT' ? 'selected' : ''; ?>>BS in Information Technology</option>
                                <option value="BSCS" <?php echo ($_POST['program'] ?? '') === 'BSCS' ? 'selected' : ''; ?>>BS in Computer Science</option>
                                <option value="BSIS" <?php echo ($_POST['program'] ?? '') === 'BSIS' ? 'selected' : ''; ?>>BS in Information Systems</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="year_completed">Year Completed *</label>
                            <input 
                                type="number" 
                                id="year_completed" 
                                name="year_completed" 
                                min="2000" 
                                max="2100"
                                placeholder="e.g., 2026"
                                value="<?php echo htmlspecialchars($_POST['year_completed'] ?? ''); ?>"
                                required
                                class="form-input"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="abstract">Abstract *</label>
                        <textarea 
                            id="abstract" 
                            name="abstract" 
                            placeholder="Provide a brief abstract of your project"
                            required
                            class="form-textarea"
                        ><?php echo htmlspecialchars($_POST['abstract'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="keywords">Keywords</label>
                            <input 
                                type="text" 
                                id="keywords" 
                                name="keywords" 
                                placeholder="Comma-separated keywords"
                                value="<?php echo htmlspecialchars($_POST['keywords'] ?? ''); ?>"
                                class="form-input"
                            >
                        </div>

                        <div class="form-group">
                            <label for="adviser">Adviser</label>
                            <input 
                                type="text" 
                                id="adviser" 
                                name="adviser" 
                                placeholder="Your adviser's name"
                                value="<?php echo htmlspecialchars($_POST['adviser'] ?? ''); ?>"
                                class="form-input"
                            >
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Project File</h2>
                    
                    <div class="form-group">
                        <label for="project_file">Project PDF (Max 15 MB) *</label>
                        <div class="file-input-wrapper" id="fileInputWrapper">
                            <input 
                                type="file" 
                                id="project_file" 
                                name="project_file" 
                                accept="application/pdf" 
                                required
                                class="file-input"
                            >
                            <label for="project_file" class="file-input-label">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                <span>Click to upload or drag and drop</span>
                                <small>PDF files only, up to 15 MB</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Upload Project</button>
                    <a href="../student/student_dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>

<script src="../assets/notifications.js"></script>
<script>
// File input drag and drop handling
const fileInputWrapper = document.getElementById('fileInputWrapper');
const fileInput = document.getElementById('project_file');
const fileLabel = document.querySelector('.file-input-label');

if (fileInputWrapper && fileInput && fileLabel) {
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileInputWrapper.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        fileInputWrapper.addEventListener(eventName, () => {
            fileInputWrapper.classList.add('highlight');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileInputWrapper.addEventListener(eventName, () => {
            fileInputWrapper.classList.remove('highlight');
        }, false);
    });

    // Handle dropped files
    fileInputWrapper.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        
        // Update label text with filename
        if (files.length > 0) {
            fileLabel.querySelector('span').textContent = files[0].name;
            fileLabel.classList.add('file-selected');
        }
    }, false);

    // Handle file selection via input
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileLabel.querySelector('span').textContent = fileInput.files[0].name;
            fileLabel.classList.add('file-selected');
        }
    });
}
</script>
</body>
</html>