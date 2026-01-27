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
<!doctype html>
<html>
<head><title>Faculty Dashboard</title>
<link href="/capstone-repo/assets/custom.css" rel="stylesheet">
</head>
<body>
<header>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-brand">Capstone Portal</div>
      <a href="/capstone-repo/public/logout.php" class="btn btn-ghost btn-sm">Logout</a>
    </nav>
  </div>
</header>
<main class="container py-3">
  <div class="mb-6">
    <h1>Faculty Review Dashboard</h1>
    <p class="text-muted">Review and provide feedback on student capstone projects</p>
  </div>

  <?php if (empty($projects)): ?>
    <div class="card">
      <div class="card-body text-center py-6">
        <p class="text-muted">No projects pending review.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1">
      <?php foreach ($projects as $p): ?>
        <div class="card">
          <div class="card-header">
            <div class="flex justify-between items-center mb-2">
              <h3 class="card-title mb-0"><?php echo htmlspecialchars($p['title']); ?></h3>
              <span class="badge badge-primary"><?php echo ucwords(str_replace('_', ' ', $p['status'])); ?></span>
            </div>
            <p class="text-muted mb-0" style="font-size: 0.875rem;">by <strong><?php echo htmlspecialchars($p['uploader']); ?></strong></p>
          </div>
          <form method="post" action="/capstone-repo/public/review_submit.php" class="card-body">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="project_id" value="<?php echo $p['project_id']; ?>">
            
            <div class="form-group">
              <label for="comment_<?php echo $p['project_id']; ?>">Review Comments</label>
              <textarea id="comment_<?php echo $p['project_id']; ?>" name="comment" placeholder="Provide your detailed feedback..." required></textarea>
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

            <div class="flex gap-2">
              <button type="submit" class="btn btn-secondary">Submit Review</button>
              <button type="reset" class="btn btn-ghost">Clear</button>
            </div>
          </form>
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