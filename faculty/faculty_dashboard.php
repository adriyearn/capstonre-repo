<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('faculty');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = DB::getConnection();
$stmt = $pdo->query("SELECT p.*, u.full_name AS uploader FROM projects p JOIN users u ON p.uploader_id=u.user_id WHERE p.status IN ('submitted','under_review','revision_requested')");
$projects = $stmt->fetchAll();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Capstone Portal</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="navbar-brand">👨‍🏫 Faculty Dashboard</div>
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
            <a href="../public/logout.php" class="btn btn-ghost">Logout</a>
        </nav>
    </div>
</header>

<main>
    <div class="dashboard-header">
        <h1>Review Pending Projects</h1>
        <p>Review and provide feedback on student capstone projects</p>
    </div>

    <?php if (empty($projects)): ?>
        <div class="upload-prompt">
            <div class="upload-prompt-icon">✅</div>
            <h2>All Caught Up!</h2>
            <p>There are no projects pending review at this time.</p>
        </div>
    <?php else: ?>
        <div class="projects-container">
            <?php foreach ($projects as $p): ?>
                <div class="project-card">
                    <div class="project-header">
                        <div>
                            <h3 class="project-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                            <p class="project-header-meta">by <strong><?php echo htmlspecialchars($p['uploader']); ?></strong></p>
                        </div>
                        <?php 
                            $statusClass = match($p['status']) {
                                'submitted' => 'submitted',
                                'under_review' => 'submitted',
                                'revision_requested' => 'revision',
                                default => 'submitted'
                            };
                            $statusLabel = match($p['status']) {
                                'submitted' => 'Submitted',
                                'under_review' => 'Under Review',
                                'revision_requested' => 'Needs Revision',
                                default => ucwords(str_replace('_', ' ', $p['status']))
                            };
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                    </div>

                    <div class="project-body">
                    <div class="project-body">
                        <?php if (!empty($p['description'])): ?>
                            <div class="project-section">
                                <h4 class="project-section-title">📋 Description</h4>
                                <p class="project-section-text">
                                    <?php echo htmlspecialchars(substr($p['description'], 0, 300)); ?><?php if (strlen($p['description']) > 300) echo '...'; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="project-section file-section">
                            <h4 class="project-section-title">📎 Submitted File</h4>
                            <?php if (!empty($p['file_path']) && !empty($p['file_name_original'])): ?>
                                <div class="file-box">
                                    <div class="file-info">
                                        <svg class="file-icon" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                            <polyline points="13 2 13 9 20 9"></polyline>
                                        </svg>
                                        <div class="file-details">
                                            <p class="file-name">
                                                <?php echo htmlspecialchars($p['file_name_original']); ?>
                                            </p>
                                            <p class="file-size">
                                                <?php 
                                                    $uploadPath = __DIR__ . '/../public/uploads/' . $p['file_path'];
                                                    if (file_exists($uploadPath)) {
                                                        $filesize = filesize($uploadPath);
                                                        echo $filesize >= 1048576 ? round($filesize / 1048576, 2) . ' MB' : round($filesize / 1024, 2) . ' KB';
                                                    }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a href="../public/download.php?pid=<?php echo $p['project_id']; ?>" class="btn btn-primary">⬇️ Download</a>
                                </div>
                            <?php else: ?>
                                <div class="file-empty">
                                    <em>No file submitted</em>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="project-footer">
                        <form method="post" action="../public/review_submit.php" class="review-form">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="project_id" value="<?php echo $p['project_id']; ?>">
                            
                            <div class="form-group">
                                <label for="comment_<?php echo $p['project_id']; ?>">Review Comments</label>
                                <textarea id="comment_<?php echo $p['project_id']; ?>" name="comment" placeholder="Provide your detailed feedback..." required class="review-textarea"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="decision_<?php echo $p['project_id']; ?>">Decision</label>
                                <select id="decision_<?php echo $p['project_id']; ?>" name="decision" required>
                                    <option value="">Select Decision</option>
                                    <option value="approve">✓ Approve</option>
                                    <option value="request_revision">↻ Request Revision</option>
                                    <option value="reject">✕ Reject</option>
                                </select>
                            </div>

                            <div class="form-button-group">
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                                <button type="reset" class="btn btn-ghost">Clear</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script src="../assets/app.js"></script>
<script src="../assets/notifications.js"></script>
</body>
</html>