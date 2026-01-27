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
<!doctype html>
<html>
<head><title>Student Dashboard</title>
<link href="/capstone-repo/assets/custom.css" rel="stylesheet">
</head>
<body>
<header>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-brand">Capstone Portal</div>
      <div class="flex items-center gap-3">
        <a href="/capstone-repo/public/upload_project.php" class="btn btn-primary btn-sm">+ Upload Project</a>
        <a href="/capstone-repo/public/logout.php" class="btn btn-ghost btn-sm">Logout</a>
      </div>
    </nav>
  </div>
</header>
<main class="container py-3">
  <div class="mb-6">
    <h1>Welcome Back</h1>
    <p class="text-muted">Manage and track your capstone projects below</p>
  </div>

  <?php if (empty($projects)): ?>
    <div class="card">
      <div class="card-body text-center py-6">
        <p class="text-muted">No projects submitted yet.</p>
        <a href="/capstone-repo/public/upload_project.php" class="btn btn-primary mt-4">Upload Your First Project</a>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1">
      <?php foreach ($projects as $p): ?>
        <div class="card">
          <div class="card-header flex justify-between items-center">
            <div>
              <h3 class="card-title"><?php echo htmlspecialchars($p['title']); ?></h3>
            </div>
            <div>
              <?php 
                $statusClass = match($p['status']) {
                  'submitted' => 'badge-warning',
                  'under_review' => 'badge-primary',
                  'approved' => 'badge-success',
                  'rejected' => 'badge-danger',
                  'revision_requested' => 'badge-warning',
                  default => 'badge-gray'
                };
                $statusLabel = ucwords(str_replace('_', ' ', $p['status']));
              ?>
              <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3"><?php echo htmlspecialchars(substr($p['abstract'], 0, 150)) . (strlen($p['abstract']) > 150 ? '...' : ''); ?></p>
            <div class="grid grid-cols-2 gap-2 mb-3">
              <div>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Program</p>
                <p class="mb-0"><strong><?php echo htmlspecialchars($p['program']); ?></strong></p>
              </div>
              <div>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">Year Completed</p>
                <p class="mb-0"><strong><?php echo htmlspecialchars($p['year_completed']); ?></strong></p>
              </div>
            </div>

            <?php 
              // Fetch reviews for this project
              $reviewStmt->execute([':pid' => $p['project_id']]);
              $reviews = $reviewStmt->fetchAll();
              
              if (!empty($reviews)): 
            ?>
              <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <p style="font-weight: 600; margin-bottom: 1rem;">Faculty Reviews</p>
                <?php foreach ($reviews as $review): ?>
                  <div style="background: #f9fafb; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                      <p style="font-weight: 500; margin: 0;">
                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                        <?php 
                          $decisionBadgeClass = match($review['decision']) {
                            'approve' => 'badge-success',
                            'request_revision' => 'badge-warning',
                            'reject' => 'badge-danger',
                            default => 'badge-gray'
                          };
                          $decisionLabel = match($review['decision']) {
                            'approve' => '✓ Approved',
                            'request_revision' => '↻ Revision Requested',
                            'reject' => '✕ Rejected',
                            default => ucfirst($review['decision'])
                          };
                        ?>
                        <span class="badge <?php echo $decisionBadgeClass; ?>" style="margin-left: 0.5rem;"><?php echo $decisionLabel; ?></span>
                      </p>
                      <p class="text-muted mb-0" style="font-size: 0.75rem;"><?php echo date('M d, Y', strtotime($review['review_timestamp'] ?? 'now')); ?></p>
                    </div>
                    <p style="margin: 0; color: #374151; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($review['comment']); ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-footer flex justify-between items-center">
            <a href="/capstone-repo/public/download.php?pid=<?php echo $p['project_id']; ?>" class="btn btn-primary btn-sm">Download</a>
            <p class="text-muted mb-0" style="font-size: 0.875rem;"><?php echo date('M d, Y', strtotime($p['upload_timestamp'] ?? 'now')); ?></p>
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
</body>
</html>