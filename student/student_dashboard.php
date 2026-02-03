<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('student');
require_once __DIR__ . '/../config/db.php';

$pdo = DB::getConnection();
$stmt = $pdo->prepare("SELECT * FROM projects WHERE uploader_id = :uid ORDER BY upload_timestamp DESC");
$stmt->execute([':uid' => current_user_id()]);
$projects = $stmt->fetchAll();

// Fetch reviews for all projects
$reviewStmt = $pdo->prepare("
  SELECT r.*, u.username AS reviewer_name 
  FROM reviews r 
  JOIN users u ON r.reviewer_id = u.user_id 
  WHERE r.project_id = :pid 
  ORDER BY r.review_timestamp DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Capstone Portal</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="navbar-brand">📚 Capstone Portal</div>
        <nav class="navbar-nav">
            <div id="notification-system" class="notification-system">
                <button id="notification-bell" class="notification-bell" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span id="notification-badge" class="notification-badge hidden">0</span>
                </button>
                <div id="notification-panel" class="notification-panel hidden" role="dialog" aria-label="Notifications">
                    <div class="notification-panel-header">
                        <h4>Notifications</h4>
                        <button id="notification-close" class="notification-close" aria-label="Close notifications">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div id="notification-items" class="notification-items"></div>
                </div>
            </div>
            <a href="../public/approved_projects.php" class="btn btn-ghost">📚 Browse Projects</a>
            <a href="../public/upload_project.php" class="btn btn-primary">+ Upload Project</a>
            <a href="../public/logout.php" class="btn btn-ghost">Logout</a>
        </nav>
    </div>
</header>

<main>
    <div class="dashboard-header">
        <h1>Welcome Back!</h1>
        <p>Manage and track your capstone projects</p>
    </div>

    <?php if (empty($projects)): ?>
        <div class="upload-prompt">
            <div class="upload-prompt-icon">📤</div>
            <h2>No Projects Yet</h2>
            <p>Ready to share your capstone project? Upload it now and get feedback from faculty.</p>
            <a href="../public/upload_project.php" class="btn btn-primary">Upload Your First Project</a>
        </div>
    <?php else: ?>
        <div class="projects-container">
            <?php foreach ($projects as $p): ?>
                <div class="project-card">
                    <div class="project-header">
                        <h3 class="project-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                        <?php 
                            $statusClass = match($p['status']) {
                                'submitted' => 'submitted',
                                'under_review' => 'submitted',
                                'approved' => 'approved',
                                'rejected' => 'rejected',
                                'revision_requested' => 'revision',
                                default => 'submitted'
                            };
                            $statusLabel = match($p['status']) {
                                'submitted' => 'Submitted',
                                'under_review' => 'Under Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'revision_requested' => 'Needs Revision',
                                default => ucwords(str_replace('_', ' ', $p['status']))
                            };
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                    </div>

                    <div class="project-body">
                        <p class="project-abstract"><?php echo htmlspecialchars(substr($p['abstract'], 0, 150)) . (strlen($p['abstract']) > 150 ? '...' : ''); ?></p>

                        <div class="project-meta">
                            <div class="meta-item">
                                <span class="meta-label">Program</span>
                                <span class="meta-value"><?php echo htmlspecialchars($p['program']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Year Completed</span>
                                <span class="meta-value"><?php echo htmlspecialchars($p['year_completed']); ?></span>
                            </div>
                        </div>

                        <?php 
                            $reviewStmt->execute([':pid' => $p['project_id']]);
                            $reviews = $reviewStmt->fetchAll();
                            
                            if (!empty($reviews)): 
                        ?>
                            <div class="reviews-section">
                                <div class="reviews-title">⭐ Reviews</div>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <span class="review-by"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                            <?php 
                                                $decisionLabel = match($review['decision']) {
                                                    'approve' => '✓ Approved',
                                                    'request_revision' => '↻ Revision Needed',
                                                    'reject' => '✕ Rejected',
                                                    default => ucfirst($review['decision'])
                                                };
                                            ?>
                                            <span class="review-decision-badge"><?php echo $decisionLabel; ?></span>
                                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['review_timestamp'] ?? 'now')); ?></span>
                                        </div>
                                        <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="project-footer">
                        <a href="../public/download.php?pid=<?php echo $p['project_id']; ?>" class="btn btn-primary">⬇️ Download</a>
                        <span class="project-upload-date">Uploaded: <?php echo date('M d, Y', strtotime($p['upload_timestamp'] ?? 'now')); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<footer>
  <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>
<script src="/capstone-repo/assets/app.js"></script>
<script src="/capstone-repo/assets/notifications.js"></script>
</body>
</html>