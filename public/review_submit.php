<?php
// public/review_submit.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';


require_roles(['faculty', 'admin']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!validate_csrf($token)) {
        $errors[] = 'Invalid CSRF token';
    }

    $project_id = intval($_POST['project_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    $validDecisions = ['approve', 'request_revision', 'reject'];
    if (!in_array($decision, $validDecisions, true)) {
        $errors[] = 'Invalid decision';
    }

    if ($project_id <= 0) $errors[] = 'Invalid project id';

    if (empty($errors)) {
        $pdo = DB::getConnection();
        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO reviews (project_id, reviewer_id, comment, decision) VALUES (:pid, :rid, :comment, :decision)");
            $ins->execute([
                ':pid' => $project_id,
                ':rid' => current_user_id(),
                ':comment' => $comment,
                ':decision' => $decision
            ]);

            // Map decision to project status
            $statusMap = [
                'approve' => 'approved',
                'request_revision' => 'revision_requested',
                'reject' => 'rejected'
            ];
            $newStatus = $statusMap[$decision];

            $upd = $pdo->prepare("UPDATE projects SET status = :status WHERE project_id = :pid");
            $upd->execute([':status' => $newStatus, ':pid' => $project_id]);

            $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, target_type, target_id, details) VALUES (:uid, 'review', 'project', :pid, :details)");
            $log->execute([
                ':uid' => current_user_id(),
                ':pid' => $project_id,
                ':details' => "Decision: {$decision}; Comment: {$comment}"
            ]);

            $pdo->commit();

            // Notify uploader (fetch email)
            $stmt = $pdo->prepare("SELECT u.email FROM users u JOIN projects p ON p.uploader_id = u.user_id WHERE p.project_id = :pid");
            $stmt->execute([':pid' => $project_id]);
            $row = $stmt->fetch();
            if ($row && !empty($row['email'])) {
                // Use mailer or PHP mail() here; omitted for brevity
                // mail($row['email'], "Project review update", "Your project status: {$newStatus}");
            }

            header('Location: /capstone-repo/faculty/faculty_dashboard.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Review error: ' . $e->getMessage());
            $errors[] = 'Server error while saving review';
        }
    }
}

// Minimal response for errors
if ($errors) {
    http_response_code(400);
    echo implode("\n", array_map('htmlspecialchars', $errors));
}