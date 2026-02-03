<?php
// public/review_submit.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helper.php';
require_once __DIR__ . '/../includes/email_helper.php';


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

            // Fetch project uploader info for notification
            $projStmt = $pdo->prepare("SELECT uploader_id, title FROM projects WHERE project_id = :pid");
            $projStmt->execute([':pid' => $project_id]);
            $proj = $projStmt->fetch();

            if ($proj) {
                // Create notification for student
                $notificationTitles = [
                    'approve' => 'Project Approved! 🎉',
                    'request_revision' => 'Revision Requested',
                    'reject' => 'Project Rejected'
                ];

                $notificationMessages = [
                    'approve' => 'Congratulations! Your project "' . substr($proj['title'], 0, 40) . '" has been approved.',
                    'request_revision' => 'Please revise your project "' . substr($proj['title'], 0, 40) . '" based on feedback.',
                    'reject' => 'Unfortunately, your project "' . substr($proj['title'], 0, 40) . '" was not approved.'
                ];

                create_notification(
                    $proj['uploader_id'],
                    $notificationTitles[$decision] ?? 'Project Review Complete',
                    $notificationMessages[$decision] ?? 'Your project has been reviewed.',
                    '/capstone-repo/student/student_dashboard.php',
                    'review'
                );
            }

            // Notify other faculty about new review
            $facultyStmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'faculty' AND user_id != :uid");
            $facultyStmt->execute([':uid' => current_user_id()]);
            $facultyUsers = $facultyStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($facultyUsers)) {
                create_bulk_notifications(
                    $facultyUsers,
                    'New Review Submitted',
                    'A project review has been completed.',
                    '/capstone-repo/faculty/faculty_dashboard.php',
                    'review'
                );
            }

            // Notify admin
            $adminStmt = $pdo->prepare("SELECT user_id FROM users WHERE role = 'admin'");
            $adminStmt->execute();
            $adminUsers = $adminStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($adminUsers)) {
                create_bulk_notifications(
                    $adminUsers,
                    'Project Review Submitted',
                    'A project review has been completed and status updated.',
                    '/capstone-repo/admin/admin_dashboard.php',
                    'review'
                );
            }

            // Send email to student with review
            $studentStmt = $pdo->prepare("SELECT u.email, u.full_name FROM users u JOIN projects p ON p.uploader_id = u.user_id WHERE p.project_id = :pid");
            $studentStmt->execute([':pid' => $project_id]);
            $studentRow = $studentStmt->fetch();
            
            if ($studentRow && !empty($studentRow['email']) && $proj) {
                $reviewer_name = $_SESSION['username'] ?? 'Faculty Member';
                $reviewerStmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = :uid");
                $reviewerStmt->execute([':uid' => current_user_id()]);
                $reviewerData = $reviewerStmt->fetch();
                if ($reviewerData && !empty($reviewerData['full_name'])) {
                    $reviewer_name = $reviewerData['full_name'];
                }
                
                send_project_review_email(
                    $studentRow['email'],
                    $studentRow['full_name'] ?? 'Student',
                    $proj['title'],
                    $decision,
                    $comment,
                    $reviewer_name
                );
            }

            // Notify uploader (fetch email)
            $stmt = $pdo->prepare("SELECT u.email FROM users u JOIN projects p ON p.uploader_id = u.user_id WHERE p.project_id = :pid");
            $stmt->execute([':pid' => $project_id]);
            $row = $stmt->fetch();
            if ($row && !empty($row['email'])) {
                // Email already sent via send_project_review_email above
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