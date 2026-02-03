<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
$pdo = DB::getConnection();
$uid = current_user_id();

// unread count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
$stmt->execute([':uid'=>$uid]);
$count = (int)$stmt->fetchColumn();

// latest 10 notifications
$stmt2 = $pdo->prepare("SELECT notification_id, type, title, message, url, is_read, created_at FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
$stmt2->execute([':uid'=>$uid]);
$items = $stmt2->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['count'=>$count, 'items'=>$items]);