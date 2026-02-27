<?php
require_once 'config.php';

$filter_category = $_GET['category'] ?? 'all';

$all_projects = readJSON(PROJECTS_FILE);
$projects = array_filter($all_projects, function($p) {
    return $p['status'] == 'approved';
});

if ($filter_category != 'all') {
    $projects = array_filter($projects, function($p) use ($filter_category) {
        return $p['category'] == $filter_category;
    });
}

$user_votes = [];
$votes_per_category = [];
if (isLoggedIn()) {
    $all_votes = readJSON(VOTES_FILE);
    $user_id = getUserId();
    
    foreach ($all_votes as $vote) {
        if ($vote['user_id'] == $user_id) {
            $user_votes[] = $vote['project_id'];
            $project = getProjectById($vote['project_id']);
            if ($project) {
                $cat = $project['category'];
                $votes_per_category[$cat] = ($votes_per_category[$cat] ?? 0) + 1;
            }
        }
    }
}

$projects_by_category = [];
foreach ($projects as $project) {
    $all_votes = readJSON(VOTES_FILE);
    $vote_count = count(array_filter($all_votes, function($v) use ($project) {
        return $v['project_id'] == $project['id'];
    }));
    $project['vote_count'] = $vote_count;
    
    $owner = getUserById($project['owner']);
    $project['username'] = $owner ? $owner['username'] : 'Unknown';
    
    $projects_by_category[$project['category']][] = $project;
}

renderHeader('Home');
?>

<h2>Projects</h2>

<div class="filter-section">
    <label>Filter by category: </label>
    <select onchange="location.href='index.php?category='+this.value">
        <option value="all" <?= $filter_category == 'all' ? 'selected' : '' ?>>All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filter_category == $cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if (empty($projects_by_category)): ?>
    <div class="section">
        <p>No published projects yet.</p>
    </div>
<?php else: ?>
    <?php foreach ($projects_by_category as $category => $cat_projects): ?>
        <div class="category-section" data-category="<?= htmlspecialchars($category) ?>">
            <h3><?= htmlspecialchars($category) ?></h3>
            <?php if (isLoggedIn()): ?>
                <p class="votes-remaining">
                    Remaining votes: <span class="remaining-count"><?= 3 - ($votes_per_category[$category] ?? 0) ?></span>/3
                </p>
            <?php endif; ?>
            
            <div class="projects-list">
                <?php foreach ($cat_projects as $project): ?>
                    <?php
                    $approved_time = strtotime($project['approved']);
                    $two_weeks_later = $approved_time + (14 * 24 * 60 * 60);
                    $voting_closed = time() > $two_weeks_later;
                    
                    $has_voted = in_array($project['id'], $user_votes);
                    $can_vote = isLoggedIn() && !$voting_closed && !$has_voted && 
                               (($votes_per_category[$category] ?? 0) < 3);
                    ?>
                    <div class="project-item <?= $voting_closed ? 'voting-closed' : '' ?>" data-project-id="<?= $project['id'] ?>">
                        <div class="project-info">
                            <h4>
                                <a href="project.php?id=<?= $project['id'] ?>">
                                    <?= htmlspecialchars($project['title']) ?>
                                </a>
                            </h4>
                            <p class="project-meta">
                                By: <?= htmlspecialchars($project['username']) ?> | 
                                Votes: <span class="vote-count"><?= $project['vote_count'] ?></span>
                                <?php if ($voting_closed): ?>
                                    <span class="closed-badge">Voting Closed</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="vote-section">
                                <?php if ($has_voted && !$voting_closed): ?>
                                    <button class="btn-unvote" onclick="vote(<?= $project['id'] ?>, 'unvote', '<?= htmlspecialchars($category) ?>')">
                                        Withdraw Vote
                                    </button>
                                <?php elseif ($has_voted): ?>
                                    <span class="voted-badge">Voted</span>
                                <?php elseif ($can_vote): ?>
                                    <button class="btn-vote" onclick="vote(<?= $project['id'] ?>, 'vote', '<?= htmlspecialchars($category) ?>')">
                                        Vote
                                    </button>
                                <?php elseif ($voting_closed): ?>
                                    <span class="disabled-badge">Closed</span>
                                <?php else: ?>
                                    <span class="disabled-badge">No votes left</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function vote(projectId, action, category) {
    const button = event.target;
    const projectItem = button.closest('.project-item');
    const voteSection = button.closest('.vote-section');
    const voteCountSpan = projectItem.querySelector('.vote-count');
    const categorySection = document.querySelector('.category-section[data-category="' + category + '"]');
    const remainingCountSpan = categorySection ? categorySection.querySelector('.remaining-count') : null;
    
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = 'Processing...';
    
    fetch('handlers.php?action=vote', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'project_id=' + projectId + '&vote_action=' + action
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.vote_count !== undefined) {
                voteCountSpan.textContent = data.vote_count;
            }
            
            if (remainingCountSpan && data.remaining_votes !== undefined) {
                remainingCountSpan.textContent = data.remaining_votes;
            }
            
            if (action === 'vote') {
                voteSection.innerHTML = '<button class="btn-unvote" onclick="vote(' + projectId + ', \'unvote\', \'' + category + '\')">Withdraw Vote</button>';
            } else {
                const remaining = remainingCountSpan ? parseInt(remainingCountSpan.textContent) : 0;
                if (remaining > 0) {
                    voteSection.innerHTML = '<button class="btn-vote" onclick="vote(' + projectId + ', \'vote\', \'' + category + '\')">Vote</button>';
                } else {
                    voteSection.innerHTML = '<span class="disabled-badge">No votes left</span>';
                }
            }
        } else {
            alert(data.message || 'Operation failed');
            button.disabled = false;
            button.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error occurred');
        button.disabled = false;
        button.textContent = originalText;
    });
}
</script>

<?php renderFooter(); ?>