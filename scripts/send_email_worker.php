<?php
// scripts/send_email_worker.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = DB::getConnection();
$limit = 10;
$stmt = $pdo->prepare("SELECT * FROM email_queue WHERE attempts < 5 ORDER BY created_at ASC LIMIT :lim");
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $mail = new PHPMailer(true);
    try {
        // SMTP config - replace with your SMTP credentials
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'smtp_user';
        $mail->Password = 'smtp_pass';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'Capstone Repo');
        $mail->addAddress($row['to_email']);
        $mail->isHTML(true);
        $mail->Subject = $row['subject'];
        $mail->Body = $row['body'];

        $mail->send();

        // remove from queue on success
        $del = $pdo->prepare("DELETE FROM email_queue WHERE id = :id");
        $del->execute([':id' => $row['id']]);
    } catch (Exception $e) {
        // increment attempts and set last_attempt
        $upd = $pdo->prepare("UPDATE email_queue SET attempts = attempts + 1, last_attempt = NOW() WHERE id = :id");
        $upd->execute([':id' => $row['id']]);
        error_log("Email send failed for id {$row['id']}: " . $mail->ErrorInfo);
    }
}