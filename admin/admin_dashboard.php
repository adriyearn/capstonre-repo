<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$pdo = DB::getConnection();
$counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM projects GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$users = $pdo->query("SELECT user_id, username, role, status FROM users ORDER BY role")->fetchAll();
?>
<!doctype html>
<html>
<head><title>Admin Dashboard</title>
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
    <h1>Admin Dashboard</h1>
    <p class="text-muted">System overview and user management</p>
  </div>

  <div class="grid grid-cols-3 mb-6">
    <?php 
      $statuses = ['submitted', 'under_review', 'approved', 'rejected', 'revision_requested', 'archived'];
      foreach ($statuses as $status):
        $count = $counts[$status] ?? 0;
        $badgeClass = match($status) {
          'submitted' => 'badge-warning',
          'under_review' => 'badge-primary',
          'approved' => 'badge-success',
          'rejected', 'archived' => 'badge-danger',
          'revision_requested' => 'badge-warning',
        };
    ?>
      <div class="card">
        <div class="card-body">
          <p class="text-muted mb-2" style="font-size: 0.875rem;"><?php echo ucwords(str_replace('_', ' ', $status)); ?></p>
          <p style="font-size: 2rem; font-weight: 700; margin: 0;"><?php echo $count; ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="mb-0">User Management</h2>
    </div>
    <div class="card-body" style="overflow-x: auto;">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>#<?php echo $u['user_id']; ?></td>
              <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
              <td>
                <?php 
                  $roleBadge = match($u['role']) {
                    'admin' => 'badge-danger',
                    'faculty' => 'badge-primary',
                    'student' => 'badge-success',
                    default => 'badge-gray'
                  };
                ?>
                <span class="badge <?php echo $roleBadge; ?>"><?php echo ucfirst($u['role']); ?></span>
              </td>
              <td>
                <?php 
                  $statusBadge = $u['status'] === 'active' ? 'badge-success' : 'badge-danger';
                ?>
                <span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($u['status']); ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<footer>
  <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>
<script src="/capstone-repo/assets/app.js"></script>
</body>
</html>