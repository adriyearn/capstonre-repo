<?php
/**
 * Notification Helper Functions
 * Include this file in your PHP code to easily create and manage notifications
 * 
 * Usage Example:
 * require_once __DIR__ . '/notification_helper.php';
 * create_notification($user_id, 'Review Complete', 'Your project has been reviewed', '/capstone-repo/student/student_dashboard.php', 'review');
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Create a notification for a user
 * 
 * @param int $user_id - The user who will receive the notification
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param string $url - Optional URL to navigate to when clicked
 * @param string $type - Notification type (review, comment, approval, rejection, etc.)
 * @return int|false - Returns notification_id on success, false on failure
 */
function create_notification($user_id, $title, $message, $url = null, $type = 'info') {
  try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("
      INSERT INTO notifications (user_id, type, title, message, url, is_read, created_at)
      VALUES (:user_id, :type, :title, :message, :url, 0, NOW())
    ");
    
    $result = $stmt->execute([
      ':user_id' => $user_id,
      ':type' => $type,
      ':title' => $title,
      ':message' => $message,
      ':url' => $url
    ]);
    
    if ($result) {
      return $pdo->lastInsertId();
    }
    return false;
  } catch (Exception $e) {
    error_log("Error creating notification: " . $e->getMessage());
    return false;
  }
}

/**
 * Create notifications for multiple users
 * 
 * @param array $user_ids - Array of user IDs
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param string $url - Optional URL
 * @param string $type - Notification type
 * @return int - Count of successfully created notifications
 */
function create_bulk_notifications($user_ids, $title, $message, $url = null, $type = 'info') {
  $count = 0;
  foreach ($user_ids as $user_id) {
    if (create_notification($user_id, $title, $message, $url, $type)) {
      $count++;
    }
  }
  return $count;
}

/**
 * Get unread notification count for a user
 * 
 * @param int $user_id - The user ID
 * @return int - Count of unread notifications
 */
function get_unread_count($user_id) {
  try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
    $stmt->execute([':uid' => $user_id]);
    return (int)$stmt->fetchColumn();
  } catch (Exception $e) {
    return 0;
  }
}

/**
 * Mark a notification as read
 * 
 * @param int $notification_id - The notification ID
 * @return bool - Success status
 */
function mark_notification_read($notification_id) {
  try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :nid");
    return $stmt->execute([':nid' => $notification_id]);
  } catch (Exception $e) {
    return false;
  }
}

/**
 * Delete old notifications (older than days specified)
 * 
 * @param int $days - Delete notifications older than this many days
 * @return int - Count of deleted notifications
 */
function cleanup_old_notifications($days = 30) {
  try {
    $pdo = DB::getConnection();
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
    $stmt->execute([':days' => $days]);
    return $stmt->rowCount();
  } catch (Exception $e) {
    return 0;
  }
}

/**
 * Examples of different notification types:
 * 
 * 1. Project Review Notification
 *    create_notification($student_id, 'New Review', 'Your project has been reviewed by ' . $faculty_name, 
 *                       '/capstone-repo/student/student_dashboard.php', 'review');
 * 
 * 2. Revision Request
 *    create_notification($student_id, 'Revision Requested', 'Please revise your project based on feedback', 
 *                       '/capstone-repo/student/student_dashboard.php', 'revision');
 * 
 * 3. Project Approval
 *    create_notification($student_id, 'Project Approved', 'Congratulations! Your project has been approved', 
 *                       '/capstone-repo/student/student_dashboard.php', 'approval');
 * 
 * 4. Admin Notification
 *    create_notification($admin_id, 'New Submission', 'A student has submitted a new project', 
 *                       '/capstone-repo/admin/admin_dashboard.php', 'submission');
 */
?>
