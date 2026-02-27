<?php
require_once 'config.php';

if (!isAdmin()) {
    redirect('index.php');
}

$all_projects = readJSON(PROJECTS_FILE);
$all_votes = readJSON(VOTES_FILE);

$approved_projects = array_filter($all_projects, function($p) {
    return $p['status'] == 'approved';
});

$projects_with_votes = [];
foreach ($approved_projects as $project) {
    $vote_count = count(array_filter($all_votes, function($v) use ($project) {
        return $v['project_id'] == $project['id'];
    }));
    $project['vote_count'] = $vote_count;
    $owner = getUserById($project['owner']);
    $project['username'] = $owner ? $owner['username'] : 'Unknown';
    $projects_with_votes[] = $project;
}

usort($projects_with_votes, function($a, $b) {
    return $b['vote_count'] - $a['vote_count'];
});

$top_project = !empty($projects_with_votes) ? $projects_with_votes[0] : null;

$top_by_category = [];
foreach ($categories as $category) {
    $category_projects = array_filter($projects_with_votes, function($p) use ($category) {
        return $p['category'] == $category;
    });
    usort($category_projects, function($a, $b) {
        return $b['vote_count'] - $a['vote_count'];
    });
    $top_by_category[$category] = array_slice($category_projects, 0, 3);
}

$stats = [];
foreach ($categories as $category) {
    $stats[$category] = [];
    foreach (array_keys($statuses) as $status) {
        $count = count(array_filter($all_projects, function($p) use ($category, $status) {
            return $p['category'] == $category && $p['status'] == $status;
        }));
        $stats[$category][$status] = $count;
    }
}

renderHeader('Statistics');
?>

<h2>Statistics</h2>

<div class="section">
    <h3>Top Project by Votes</h3>
    <?php if ($top_project): ?>
        <div class="top-project">
            <h4>
                <a href="project.php?id=<?= $top_project['id'] ?>">
                    <?= htmlspecialchars($top_project['title']) ?>
                </a>
            </h4>
            <p>
                Category: <?= htmlspecialchars($top_project['category']) ?> | 
                Votes: <strong><?= $top_project['vote_count'] ?></strong> | 
                By: <?= htmlspecialchars($top_project['username']) ?>
            </p>
        </div>
    <?php else: ?>
        <p>No data available</p>
    <?php endif; ?>
</div>

<div class="section">
    <h3>Top 3 Projects by Category</h3>
    <?php foreach ($top_by_category as $category => $projects): ?>
        <div class="category-top3">
            <h4><?= htmlspecialchars($category) ?></h4>
            <?php if (empty($projects)): ?>
                <p>No projects in this category</p>
            <?php else: ?>
                <ol>
                    <?php foreach ($projects as $project): ?>
                        <li>
                            <a href="project.php?id=<?= $project['id'] ?>">
                                <?= htmlspecialchars($project['title']) ?>
                            </a>
                            - <?= $project['vote_count'] ?> votes
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <h3>Project Count by Category and Status</h3>
    
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Pending</th>
                <th>Rework</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stats as $category => $counts): ?>
                <tr>
                    <td><?= htmlspecialchars($category) ?></td>
                    <td><?= $counts['pending'] ?></td>
                    <td><?= $counts['rework'] ?></td>
                    <td><?= $counts['approved'] ?></td>
                    <td><?= $counts['rejected'] ?></td>
                    <td><strong><?= array_sum($counts) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="charts-container">
        <div class="chart-box">
            <h4>Projects by Status (Stacked)</h4>
            <canvas id="chartByStatus"></canvas>
        </div>
        <div class="chart-box">
            <h4>Projects by Category (Stacked)</h4>
            <canvas id="chartByCategory"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const categories = <?= json_encode(array_keys($stats)) ?>;
const statuses = ['pending', 'rework', 'approved', 'rejected'];
const statusLabels = ['Pending', 'Rework', 'Approved', 'Rejected'];
const data = <?= json_encode($stats) ?>;

new Chart(document.getElementById('chartByStatus'), {
    type: 'bar',
    data: {
        labels: statusLabels,
        datasets: categories.map((cat, i) => ({
            label: cat,
            data: [data[cat].pending, data[cat].rework, data[cat].approved, data[cat].rejected],
            backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444'][i % 4]
        }))
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});

new Chart(document.getElementById('chartByCategory'), {
    type: 'bar',
    data: {
        labels: categories,
        datasets: [
            {
                label: 'Pending',
                data: categories.map(cat => data[cat].pending),
                backgroundColor: '#3b82f6'
            },
            {
                label: 'Rework',
                data: categories.map(cat => data[cat].rework),
                backgroundColor: '#f59e0b'
            },
            {
                label: 'Approved',
                data: categories.map(cat => data[cat].approved),
                backgroundColor: '#10b981'
            },
            {
                label: 'Rejected',
                data: categories.map(cat => data[cat].rejected),
                backgroundColor: '#ef4444'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true }
        }
    }
});
</script>

<?php renderFooter(); ?>