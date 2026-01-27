<?php
// public/download.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';


// Allow guests to view abstracts only; downloads require login or approved public flag.
// For this template, require login for download; adjust policy as needed.
require_login();

$project_id = intval($_GET['pid'] ?? 0);
if ($project_id <= 0) {
    http_response_code(400);
    exit('Invalid project id');
}

$pdo = DB::getConnection();
$stmt = $pdo->prepare("SELECT p.file_path, p.file_name_original, p.status, p.uploader_id FROM projects p WHERE p.project_id = :pid LIMIT 1");
$stmt->execute([':pid' => $project_id]);
$proj = $stmt->fetch();

if (!$proj) {
    http_response_code(404);
    exit('Project not found');
}

// Access policy: allow download if approved OR user is uploader OR role is admin/faculty
$role = $_SESSION['role'] ?? null;
$uid = current_user_id();
$allowed = false;
if ($proj['status'] === 'approved') $allowed = true;
if ($uid === (int)$proj['uploader_id']) $allowed = true;
if (in_array($role, ['admin', 'faculty'], true)) $allowed = true;

if (!$allowed) {
    http_response_code(403);
    exit('Forbidden');
}

$uploadDir = __DIR__ . '/uploads';
$filepath = $uploadDir . DIRECTORY_SEPARATOR . $proj['file_path'];
if (!is_file($filepath)) {
    http_response_code(404);
    exit('File missing');
}

// Log download
$log = $pdo->prepare("INSERT INTO audit_logs (user_id, action, target_type, target_id, details) VALUES (:uid, 'download', 'project', :pid, :details)");
$log->execute([
    ':uid' => $uid ?: null,
    ':pid' => $project_id,
    ':details' => 'Downloaded: ' . $proj['file_name_original']
]);

// Stream file with headers
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes(basename($proj['file_name_original'])) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate');
header('Pragma: public');
ob_clean();
flush();
readfile($filepath);
exit;