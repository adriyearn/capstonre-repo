<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
$pdo = DB::getConnection();
$uid = current_user_id();
$nid = intval($_POST['nid'] ?? 0);
if ($nid > 0) {
  $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :nid AND user_id = :uid");
  $stmt->execute([':nid'=>$nid, ':uid'=>$uid]);
}
http_response_code(204);