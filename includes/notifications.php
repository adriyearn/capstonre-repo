<?php
// includes/notifications.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

function push_notification(int $userId, string $type, string $title, string $message, ?string $url = null): void {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, url) VALUES (:uid, :type, :title, :msg, :url)");
    $stmt->execute([
        ':uid' => $userId,
        ':type' => $type,
        ':title' => $title,
        ':msg' => $message,
        ':url' => $url
    ]);
}

function queue_email(string $toEmail, string $subject, string $body): void {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("INSERT INTO email_queue (to_email, subject, body) VALUES (:to, :sub, :body)");
    $stmt->execute([':to' => $toEmail, ':sub' => $subject, ':body' => $body]);
}
