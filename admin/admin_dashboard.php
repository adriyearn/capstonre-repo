<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/db.php';

$pdo = DB::getConnection();
$counts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM projects GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$users = $pdo->query("SELECT user_id, username, role, status FROM users ORDER BY role")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Capstone Portal</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="navbar-brand">⚙️ Admin Dashboard</div>
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
        <h1>System Overview</h1>
        <p>Project statistics and user management</p>
    </div>

    <div class="stats-grid">
        <?php 
            $statuses = ['submitted', 'under_review', 'approved', 'rejected', 'revision_requested', 'archived'];
            foreach ($statuses as $status):
                $count = $counts[$status] ?? 0;
        ?>
            <div class="stat-card">
                <p class="stat-label"><?php echo ucwords(str_replace('_', ' ', $status)); ?></p>
                <p class="stat-value"><?php echo $count; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>👥 User Management</h2>
        </div>
        <div class="card-body table-body">
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
<script src="/capstone-repo/assets/notifications.js"></script>
</body>
</html>