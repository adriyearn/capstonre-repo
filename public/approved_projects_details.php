<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Get project ID from URL
$projectId = $_GET['pid'] ?? null;

if (!$projectId || !is_numeric($projectId)) {
    die("Invalid project ID");
}

$pdo = DB::getConnection();

// Get project details from approved_projects_view
$projectStmt = $pdo->prepare("SELECT * FROM approved_projects_view WHERE project_id = ?");
$projectStmt->execute([$projectId]);
$project = $projectStmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    die("Project not found or not approved");
}

// Get reviews for this project
$reviewStmt = $pdo->prepare("
    SELECT r.*, u.full_name as reviewer_name 
    FROM reviews r 
    JOIN users u ON r.reviewed_by = u.user_id 
    WHERE r.project_id = ? 
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$projectId]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

// Get reviewer info for the uploader
$uploaderStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$uploaderStmt->execute([$project['uploader_id']]);
$uploader = $uploaderStmt->fetch(PDO::FETCH_ASSOC);

// Get initials for avatar
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper($part[0]);
    }
    return substr($initials, 0, 2);
}

// Get decision badge color
function getDecisionColor($decision) {
    switch ($decision) {
        case 'approved':
            return '#10b981';
        case 'request_revision':
            return '#f59e0b';
        case 'rejected':
            return '#ef4444';
        default:
            return '#6b7280';
    }
}

function getDecisionLabel($decision) {
    switch ($decision) {
        case 'approved':
            return '✅ Approved';
        case 'request_revision':
            return '🔄 Revision Requested';
        case 'rejected':
            return '❌ Rejected';
        default:
            return '⏳ Pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Approved Projects</title>
    <link href="../assets/approved_projects_details.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="navbar">
            <div class="navbar-brand">📚 Project Details</div>
            <a href="approved_projects.php" class="btn btn-ghost">← Back to Projects</a>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <div class="project-header">
            <h1><?php echo htmlspecialchars($project['title']); ?></h1>
            <div class="header-meta">
                <div class="header-meta-item">
                    <span class="header-meta-label">Program</span>
                    <span class="header-meta-value"><?php echo htmlspecialchars($project['program']); ?></span>
                </div>
                <div class="header-meta-item">
                    <span class="header-meta-label">Year Completed</span>
                    <span class="header-meta-value"><?php echo htmlspecialchars($project['year_completed']); ?></span>
                </div>
                <div class="header-meta-item">
                    <span class="header-meta-label">Adviser</span>
                    <span class="header-meta-value"><?php echo htmlspecialchars($project['adviser']); ?></span>
                </div>
                <div class="header-meta-item">
                    <span class="header-meta-label">Published</span>
                    <span class="header-meta-value"><?php echo date('M d, Y', strtotime($project['upload_timestamp'])); ?></span>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <div>
                <!-- Abstract Section -->
                <div class="project-section">
                    <h2>📄 Abstract</h2>
                    <p class="abstract"><?php echo htmlspecialchars($project['abstract']); ?></p>
                    
                    <?php if (!empty($project['keywords'])): ?>
                    <h3 class="keywords-inline-section">Keywords</h3>
                    <div class="keywords">
                        <?php 
                        $keywords = array_filter(array_map('trim', explode(',', $project['keywords'])));
                        foreach ($keywords as $keyword): 
                        ?>
                            <span class="keyword-badge"><?php echo htmlspecialchars($keyword); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Reviews Section -->
                <div class="project-section reviews-section">
                    <h2>⭐ Reviews</h2>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="no-reviews">
                            No reviews yet for this project.
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="review-reviewer"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                    <span class="review-decision" data-decision="<?php echo $review['decision']; ?>">
                                        <?php echo getDecisionLabel($review['decision']); ?>
                                    </span>
                                </div>
                                <div class="review-date">
                                    <?php echo date('M d, Y \a\t g:i A', strtotime($review['created_at'])); ?>
                                </div>
                                <?php if (!empty($review['comments'])): ?>
                                <div class="review-comment">
                                    <strong>Comment:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($review['comments'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Author Card -->
                <div class="sidebar-card">
                    <h3>👤 Author</h3>
                    <div class="author-card">
                        <div class="author-avatar"><?php echo getInitials($project['uploader_name']); ?></div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($project['uploader_name']); ?></h4>
                            <?php if ($uploader && !empty($uploader['email'])): ?>
                            <p><?php echo htmlspecialchars($uploader['email']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Project Info Card -->
                <div class="sidebar-card">
                    <h3>📊 Project Info</h3>
                    <div class="section-spacing">
                        <div>
                            <strong>Program:</strong><br>
                            <?php echo htmlspecialchars($project['program']); ?>
                        </div>
                        <div>
                            <strong>Year:</strong><br>
                            <?php echo htmlspecialchars($project['year_completed']); ?>
                        </div>
                        <div>
                            <strong>Adviser:</strong><br>
                            <?php echo htmlspecialchars($project['adviser']); ?>
                        </div>
                        <div>
                            <strong>Published:</strong><br>
                            <?php echo date('M d, Y', strtotime($project['upload_timestamp'])); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Download Button -->
                <a href="download.php?pid=<?php echo $project['project_id']; ?>" class="btn btn-primary">
                    ⬇️ Download Project
                </a>
            </div>
        </div>
    </main>
    
    <footer>
        <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
    </footer>
</body>
</html>
