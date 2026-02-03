<?php
// public/approved_projects.php
require_once __DIR__ . '/../config/db.php';
session_start();
require_once __DIR__ . '/../includes/auth_check.php';

// Require authentication to view approved projects
require_login();

$pdo = DB::getConnection();
$currentUserId = current_user_id();
$userRole = $_SESSION['role'] ?? 'student';

// Get search parameters
$searchQuery = trim($_GET['search'] ?? '');
$programFilter = $_GET['program'] ?? '';
$yearFilter = $_GET['year'] ?? '';
$sortBy = $_GET['sort'] ?? 'recent';

// Build query based on filters
$query = "SELECT * FROM approved_projects_view WHERE 1=1";
$params = [];
$paramIndex = 1;

// Search in title, abstract, keywords
if (!empty($searchQuery)) {
    $searchTerm = '%' . $searchQuery . '%';
    $query .= " AND (title LIKE ? OR abstract LIKE ? OR keywords LIKE ? OR uploader_name LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Filter by program
if (!empty($programFilter) && in_array($programFilter, ['BSIT', 'BSCS', 'BSIS'])) {
    $query .= " AND program = ?";
    $params[] = $programFilter;
}

// Filter by year
if (!empty($yearFilter) && is_numeric($yearFilter)) {
    $query .= " AND year_completed = ?";
    $params[] = (int)$yearFilter;
}

// Add sorting
switch ($sortBy) {
    case 'oldest':
        $query .= " ORDER BY upload_timestamp ASC";
        break;
    case 'title':
        $query .= " ORDER BY title ASC";
        break;
    case 'author':
        $query .= " ORDER BY uploader_name ASC";
        break;
    case 'recent':
    default:
        $query .= " ORDER BY upload_timestamp DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Get unique years for filter
$yearStmt = $pdo->query("SELECT DISTINCT year_completed FROM approved_projects_view ORDER BY year_completed DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// Get count statistics
$countStmt = $pdo->query("SELECT COUNT(*) as total FROM approved_projects_view");
$totalProjects = $countStmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Projects - Capstone Portal</title>
    <link href="../assets/custom.css" rel="stylesheet">
    <link href="../assets/approved_projects.css" rel="stylesheet">
    <link href="../assets/notifications.css" rel="stylesheet">
</head>
<body>
<header>
    <div class="navbar">
        <div class="navbar-brand">📚 Capstone Portal</div>
        <nav class="navbar-nav">
            <a href="<?php echo ($userRole === 'faculty' ? '../faculty/faculty_dashboard.php' : ($userRole === 'admin' ? '../admin/admin_dashboard.php' : '../student/student_dashboard.php')); ?>">← Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="container">
    <!-- Search Section -->
    <div class="search-section">
        <h1>📚 Approved Projects Repository</h1>
        <p>Explore and download verified capstone projects</p>
        
        <form method="get" action="" class="search-form">
            <input 
                type="text" 
                name="search" 
                placeholder="Search by title, keywords, or author..." 
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                class="search-input"
                aria-label="Search projects"
            >
            <button type="submit" class="search-button">Search</button>
        </form>
        
        <form method="get" action="" class="filters" id="filter-form">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <div class="filter-group">
                <label for="program">Program</label>
                <select name="program" id="program" onchange="document.getElementById('filter-form').submit()" aria-label="Filter by program">
                    <option value="">All Programs</option>
                    <option value="BSIT" <?php echo $programFilter === 'BSIT' ? 'selected' : ''; ?>>BS in Information Technology</option>
                    <option value="BSCS" <?php echo $programFilter === 'BSCS' ? 'selected' : ''; ?>>BS in Computer Science</option>
                    <option value="BSIS" <?php echo $programFilter === 'BSIS' ? 'selected' : ''; ?>>BS in Information Systems</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="year">Year Completed</label>
                <select name="year" id="year" onchange="document.getElementById('filter-form').submit()" aria-label="Filter by year">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?php echo $year; ?>" <?php echo $yearFilter == $year ? 'selected' : ''; ?>>
                            <?php echo $year; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort">Sort By</label>
                <select name="sort" id="sort" onchange="document.getElementById('filter-form').submit()" aria-label="Sort results">
                    <option value="recent" <?php echo $sortBy === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>By Title (A-Z)</option>
                    <option value="author" <?php echo $sortBy === 'author' ? 'selected' : ''; ?>>By Author</option>
                </select>
            </div>
        </form>
        
        <div class="stats">
            <div class="stat-item">
                <strong><?php echo $totalProjects; ?></strong> Projects
            </div>
            <div class="stat-item">
                <strong><?php echo count($projects); ?></strong> Results
            </div>
        </div>
    </div>
    
    <!-- Projects Table -->
    <div class="results-header">
        <h2>Projects (<?php echo count($projects); ?> of <?php echo $totalProjects; ?>)</h2>
        <span class="results-count">Showing results</span>
    </div>
    
    <?php if (empty($projects)): ?>
        <div class="no-results">
            <p>No projects found. Try adjusting your filters or search terms.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Program</th>
                        <th>Year</th>
                        <th>Adviser</th>
                        <th>Date Approved</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr class="project-row">
                            <td class="col-title">
                                <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                <?php if (!empty($project['abstract'])): ?>
                                    <div class="project-abstract-short">
                                        <?php echo htmlspecialchars(substr($project['abstract'], 0, 100)); ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="col-author"><?php echo htmlspecialchars($project['uploader_name']); ?></td>
                            <td class="col-program">
                                <span class="badge-program"><?php echo htmlspecialchars($project['program']); ?></span>
                            </td>
                            <td class="col-year"><?php echo $project['year_completed']; ?></td>
                            <td class="col-adviser"><?php echo !empty($project['adviser']) ? htmlspecialchars($project['adviser']) : '—'; ?></td>
                            <td class="col-date"><?php echo date('M d, Y', strtotime($project['upload_timestamp'])); ?></td>
                            <td class="col-actions">
                                <a href="download.php?pid=<?php echo $project['project_id']; ?>" class="btn-table-download">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; 2026 Capstone Project Management System. All rights reserved.</p>
</footer>

<script src="../assets/notifications.js"></script>
</body>
</html>
