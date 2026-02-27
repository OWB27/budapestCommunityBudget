<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$all_projects = readJSON(PROJECTS_FILE);
$user_id = getUserId();
$user_projects = array_filter($all_projects, function($p) use ($user_id) {
    return isset($p['owner']) && $p['owner'] == $user_id;
});

usort($user_projects, function($a, $b) {
    $order = ['rework' => 1, 'pending' => 2, 'approved' => 3, 'rejected' => 4];
    $a_order = isset($order[$a['status']]) ? $order[$a['status']] : 5;
    $b_order = isset($order[$b['status']]) ? $order[$b['status']] : 5;
    if ($a_order == $b_order) {
        return strtotime($b['submitted']) - strtotime($a['submitted']);
    }
    return $a_order - $b_order;
});

foreach ($user_projects as &$project) {
    $all_votes = readJSON(VOTES_FILE);
    $vote_count = count(array_filter($all_votes, function($v) use ($project) {
        return $v['project_id'] == $project['id'];
    }));
    $project['vote_count'] = $vote_count;
}

renderHeader('My Projects');
?>

<h2>My Projects</h2>

<?php if ($success_message): ?>
    <div class="success-box"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<?php if (empty($user_projects)): ?>
    <div class="section">
        <p>You haven't submitted any projects yet.</p>
        <a href="auth.php?action=new_project" class="btn-primary">Submit New Project</a>
    </div>
<?php else: ?>
    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Votes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_projects as $project): ?>
                    <tr class="status-row-<?= $project['status'] ?>">
                        <td><?= htmlspecialchars($project['title']) ?></td>
                        <td><?= htmlspecialchars($project['category']) ?></td>
                        <td>
                            <span class="status-badge status-<?= $project['status'] ?>">
                                <?= $statuses[$project['status']] ?>
                            </span>
                            <?php if ($project['status'] == 'rework'): ?>
                                <span class="alert-badge">Action Needed</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($project['submitted'])) ?></td>
                        <td><?= $project['vote_count'] ?></td>
                        <td>
                            <a href="project.php?id=<?= $project['id'] ?>" class="btn-small btn-primary">View</a>
                            <?php if ($project['status'] == 'rework'): ?>
                                <a href="auth.php?action=edit_project&id=<?= $project['id'] ?>" class="btn-small btn-warning">Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php renderFooter(); ?>