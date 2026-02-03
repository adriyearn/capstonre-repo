<?php
/**
 * Test Notifications
 * This file can be accessed to create test notifications
 * Run once then delete
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/notification_helper.php';

// Only allow if logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in";
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = DB::getConnection();

// Get user info
$stmt = $pdo->prepare("SELECT role, username FROM users WHERE user_id = :uid");
$stmt->execute([':uid' => $user_id]);
$user = $stmt->fetch();

// Get all admin users
$adminStmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role = 'admin'");
$adminStmt->execute();
$admins = $adminStmt->fetchAll();

// Get all faculty users
$facultyStmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role = 'faculty'");
$facultyStmt->execute();
$faculty = $facultyStmt->fetchAll();

// Get all student users
$studentStmt = $pdo->prepare("SELECT user_id, username FROM users WHERE role = 'student'");
$studentStmt->execute();
$students = $studentStmt->fetchAll();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notifications</title>
    <link href="/capstone-repo/assets/custom.css" rel="stylesheet">
    <style>
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .test-button {
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background 0.2s;
        }
        .test-button:hover {
            background: #1e40af;
        }
        .results {
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success {
            color: #059669;
        }
        .error {
            color: #dc2626;
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 800px; margin: 2rem auto;">
    <h1>Notification System Test</h1>
    <p>Logged in as: <strong><?php echo htmlspecialchars($user['username']); ?></strong> (<?php echo htmlspecialchars($user['role']); ?>)</p>

    <div class="test-section">
        <h2>Create Test Notifications</h2>
        
        <h3>Test 1: Send yourself a test notification</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="test_self">
            <button type="submit" class="test-button">Create Self Notification</button>
        </form>

        <h3>Test 2: Send test to all admins</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="test_admins">
            <button type="submit" class="test-button">Notify All Admins</button>
        </form>

        <h3>Test 3: Send test to all faculty</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="test_faculty">
            <button type="submit" class="test-button">Notify All Faculty</button>
        </form>

        <h3>Test 4: Send test to all students</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="test_students">
            <button type="submit" class="test-button">Notify All Students</button>
        </form>
    </div>

    <div class="test-section">
        <h2>System Status</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <h4>Total Users by Role:</h4>
                <ul>
                    <li>Admins: <?php echo count($admins); ?></li>
                    <li>Faculty: <?php echo count($faculty); ?></li>
                    <li>Students: <?php echo count($students); ?></li>
                </ul>
            </div>
            <div>
                <h4>Your Notifications:</h4>
                <?php
                    $notifStmt = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :uid AND is_read = 0");
                    $notifStmt->execute([':uid' => $user_id]);
                    $notifCount = $notifStmt->fetch();
                ?>
                <ul>
                    <li>Unread: <strong><?php echo $notifCount['unread']; ?></strong></li>
                </ul>
            </div>
        </div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $result = '';

        try {
            switch ($action) {
                case 'test_self':
                    $nid = create_notification(
                        $user_id,
                        'Test Notification',
                        'This is a test notification sent to yourself at ' . date('Y-m-d H:i:s'),
                        '/capstone-repo/student/student_dashboard.php',
                        'test'
                    );
                    $result = $nid ? "✓ Notification created (ID: $nid)" : "✗ Failed to create notification";
                    break;

                case 'test_admins':
                    if (empty($admins)) {
                        $result = "✗ No admins found in system";
                    } else {
                        $admin_ids = array_column($admins, 'user_id');
                        $count = create_bulk_notifications(
                            $admin_ids,
                            'Test Notification for Admins',
                            'This is a system test notification sent at ' . date('Y-m-d H:i:s'),
                            '/capstone-repo/admin/admin_dashboard.php',
                            'test'
                        );
                        $result = "✓ Created $count notifications for " . count($admins) . " admin(s)";
                    }
                    break;

                case 'test_faculty':
                    if (empty($faculty)) {
                        $result = "✗ No faculty found in system";
                    } else {
                        $faculty_ids = array_column($faculty, 'user_id');
                        $count = create_bulk_notifications(
                            $faculty_ids,
                            'Test Notification for Faculty',
                            'This is a system test notification sent at ' . date('Y-m-d H:i:s'),
                            '/capstone-repo/faculty/faculty_dashboard.php',
                            'test'
                        );
                        $result = "✓ Created $count notifications for " . count($faculty) . " faculty member(s)";
                    }
                    break;

                case 'test_students':
                    if (empty($students)) {
                        $result = "✗ No students found in system";
                    } else {
                        $student_ids = array_column($students, 'user_id');
                        $count = create_bulk_notifications(
                            $student_ids,
                            'Test Notification for Students',
                            'This is a system test notification sent at ' . date('Y-m-d H:i:s'),
                            '/capstone-repo/student/student_dashboard.php',
                            'test'
                        );
                        $result = "✓ Created $count notifications for " . count($students) . " student(s)";
                    }
                    break;
            }
        } catch (Exception $e) {
            $result = "✗ Error: " . $e->getMessage();
        }

        if ($result) {
            echo '<div class="test-section">';
            echo '<h2>Test Result</h2>';
            echo '<div class="results ' . (strpos($result, '✓') === 0 ? 'success' : 'error') . '">' . htmlspecialchars($result) . '</div>';
            echo '</div>';
        }
    }
    ?>

    <div class="test-section">
        <h2>Recent Notifications in Database</h2>
        <?php
        $recentStmt = $pdo->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
        $recentStmt->execute();
        $recent = $recentStmt->fetchAll();
        
        if (empty($recent)) {
            echo '<p style="color: #6b7280;">No notifications in database yet</p>';
        } else {
            echo '<table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">';
            echo '<tr style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb;">';
            echo '<th style="padding: 0.5rem; text-align: left;">ID</th>';
            echo '<th style="padding: 0.5rem; text-align: left;">User</th>';
            echo '<th style="padding: 0.5rem; text-align: left;">Title</th>';
            echo '<th style="padding: 0.5rem; text-align: left;">Read</th>';
            echo '<th style="padding: 0.5rem; text-align: left;">Created</th>';
            echo '</tr>';
            
            foreach ($recent as $notif) {
                $userStmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :uid");
                $userStmt->execute([':uid' => $notif['user_id']]);
                $notifUser = $userStmt->fetch();
                
                echo '<tr style="border-bottom: 1px solid #e5e7eb;">';
                echo '<td style="padding: 0.5rem;">' . $notif['notification_id'] . '</td>';
                echo '<td style="padding: 0.5rem;">' . htmlspecialchars($notifUser['username'] ?? 'Unknown') . '</td>';
                echo '<td style="padding: 0.5rem;">' . htmlspecialchars($notif['title']) . '</td>';
                echo '<td style="padding: 0.5rem;">' . ($notif['is_read'] ? 'Yes' : 'No') . '</td>';
                echo '<td style="padding: 0.5rem;">' . date('M d, H:i', strtotime($notif['created_at'])) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>

    <p style="margin-top: 2rem; color: #6b7280; font-size: 0.875rem;">
        <strong>Note:</strong> After testing, please visit your dashboard. The notification bell should show unread notifications.
        You can delete this file after testing.
    </p>
</div>
</body>
</html>
