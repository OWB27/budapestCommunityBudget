<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('index.php');
}

$all_projects = readJSON(PROJECTS_FILE);

$pending_projects = array_filter($all_projects, function($p) {
    return isset($p['status']) && $p['status'] == 'pending';
});

$projects_by_category = [];
foreach ($pending_projects as $project) {
    $owner = getUserById($project['owner']);
    $project['username'] = $owner ? $owner['username'] : 'Unknown';
    $category = $project['category'] ?? 'Unknown';
    $projects_by_category[$category][] = $project;
}

foreach ($projects_by_category as &$projects) {
    usort($projects, function($a, $b) {
        $time_a = isset($a['submitted']) ? strtotime($a['submitted']) : 0;
        $time_b = isset($b['submitted']) ? strtotime($b['submitted']) : 0;
        return $time_b - $time_a;
    });
}

renderHeader('Admin - Manage Projects');
?>

<h2>Pending Projects for Review</h2>

<?php if (empty($projects_by_category)): ?>
    <div class="section">
        <p><strong>No pending projects at the moment.</strong></p>
        <p>Pending projects will appear here when users submit new projects.</p>
    </div>
<?php else: ?>
    <div class="section">
        <p style="margin-bottom: 1rem;">
            <strong><?= count($pending_projects) ?></strong> project(s) waiting for review
        </p>
        
        <?php foreach ($projects_by_category as $category => $cat_projects): ?>
            <div class="category-section">
                <h3><?= htmlspecialchars($category) ?> (<?= count($cat_projects) ?> project<?= count($cat_projects) > 1 ? 's' : '' ?>)</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Submitted by</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cat_projects as $project): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($project['title']) ?></strong><br>
                                    <small style="color: #666;">
                                        Postal: <?= htmlspecialchars($project['postal_code']) ?>
                                    </small>
                                </td>
                                <td><?= htmlspecialchars($project['username']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($project['submitted'])) ?></td>
                                <td>
                                    <a href="project.php?id=<?= $project['id'] ?>" class="btn-small btn-primary">
                                        View & Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="section" style="margin-top: 2rem; background: #f8f9fa;">
    <h3>How to Review Projects</h3>
    <ol>
        <li>Click "View & Review" to see the project details</li>
        <li>On the project page, you can:
            <ul>
                <li><strong>Approve & Publish</strong> - Make it visible to all users</li>
                <li><strong>Reject</strong> - Decline the project</li>
                <li><strong>Send for Rework</strong> - Ask the user to make changes</li>
            </ul>
        </li>
    </ol>
</div>

<?php renderFooter(); ?>