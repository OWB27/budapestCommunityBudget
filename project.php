<?php
require_once 'config.php';

$project_id = intval($_GET['id'] ?? 0);
if ($project_id == 0) redirect('index.php');

$project = getProjectById($project_id);
if (!$project) redirect('index.php');

if ($project['status'] != 'approved') {
    if (!isLoggedIn() || (getUserId() != $project['owner'] && !isAdmin())) {
        redirect('index.php');
    }
}

$all_votes = readJSON(VOTES_FILE);
$vote_count = count(array_filter($all_votes, function($v) use ($project_id) {
    return $v['project_id'] == $project_id;
}));

$owner = getUserById($project['owner']);

$comments = [];
if ($project['status'] == 'rework') {
    $all_comments = readJSON(COMMENTS_FILE);
    foreach ($all_comments as $comment) {
        if ($comment['project_id'] == $project_id) {
            $admin = getUserById($comment['admin_id']);
            $comment['admin_username'] = $admin ? $admin['username'] : 'Admin';
            $comments[] = $comment;
        }
    }
    usort($comments, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

$voting_closed = false;
$has_voted = false;
if ($project['approved']) {
    $approved_time = strtotime($project['approved']);
    $two_weeks_later = $approved_time + (14 * 24 * 60 * 60);
    $voting_closed = time() > $two_weeks_later;
}

if (isLoggedIn()) {
    $user_id = getUserId();
    foreach ($all_votes as $vote) {
        if ($vote['user_id'] == $user_id && $vote['project_id'] == $project_id) {
            $has_voted = true;
            break;
        }
    }
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

renderHeader($project['title']);
?>

<?php if ($success_message): ?>
    <div class="success-box"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<div class="project-detail">
    <div class="project-header">
        <h2><?= htmlspecialchars($project['title']) ?></h2>
        <span class="status-badge status-<?= $project['status'] ?>">
            <?= $statuses[$project['status']] ?>
        </span>
    </div>
    
    <?php if ($project['image']): ?>
        <div class="project-image">
            <img src="<?= htmlspecialchars($project['image']) ?>" alt="Project image">
        </div>
    <?php endif; ?>
    
    <div class="info-grid">
        <div class="info-item">
            <strong>Project ID:</strong>
            <?= $project['id'] ?>
        </div>
        <div class="info-item">
            <strong>Category:</strong>
            <?= htmlspecialchars($project['category']) ?>
        </div>
        <div class="info-item">
            <strong>Postal Code:</strong>
            <?= htmlspecialchars($project['postal_code']) ?>
        </div>
        <div class="info-item">
            <strong>Submitted by:</strong>
            <?= htmlspecialchars($owner['username']) ?>
        </div>
        <div class="info-item">
            <strong>Submitted:</strong>
            <?= date('Y-m-d H:i', strtotime($project['submitted'])) ?>
        </div>
        <?php if ($project['approved']): ?>
            <div class="info-item">
                <strong>Published:</strong>
                <?= date('Y-m-d H:i', strtotime($project['approved'])) ?>
            </div>
            <div class="info-item">
                <strong>Votes:</strong>
                <span id="vote-count"><?= $vote_count ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="margin: 2rem 0;">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($project['description'])) ?></p>
    </div>
    
    <?php if (!empty($comments)): ?>
        <div class="admin-comments">
            <h3>Admin Comments (<?= count($comments) ?>)</h3>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <p><strong><?= htmlspecialchars($comment['admin_username']) ?></strong> 
                       at <?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></p>
                    <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isAdmin() && $project['status'] == 'pending'): ?>
        <div class="admin-actions" style="border: 3px solid #f39c12; padding: 2rem; background: #fff; margin: 2rem 0; border-radius: 8px;">
            <h3 style="color: #f39c12; margin-bottom: 1.5rem;">Admin Review Required</h3>
            
            <p style="margin-bottom: 1.5rem; font-weight: bold;">
                Choose an action for this project:
            </p>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                <form method="POST" action="handlers.php?action=approve" style="flex: 1; min-width: 200px;">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <button type="submit" class="btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem;" onclick="return confirm('Approve and publish this project?')">
                        Approve & Publish
                    </button>
                </form>
                
                <form method="POST" action="handlers.php?action=reject" style="flex: 1; min-width: 200px;">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <button type="submit" class="btn-danger" style="width: 100%; padding: 1rem; font-size: 1.1rem;" onclick="return confirm('Reject this project?')">
                        Reject Project
                    </button>
                </form>
            </div>
            
            <div class="rework-form" style="border-top: 2px solid #ddd; padding-top: 1.5rem; margin-top: 1.5rem;">
                <h4 style="margin-bottom: 1rem;">Or send back for modifications:</h4>
                <form method="POST" action="handlers.php?action=rework">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <textarea name="comment" rows="4" placeholder="Enter feedback for the user (required)..." required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 1rem; font-size: 1rem;"></textarea>
                    <button type="submit" class="btn-warning" style="padding: 0.75rem 1.5rem;">
                        Send for Rework
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isLoggedIn() && getUserId() == $project['owner'] && $project['status'] == 'rework'): ?>
        <div class="edit-notice" style="border: 3px solid #f39c12; padding: 2rem; background: #fff3cd; margin: 2rem 0; border-radius: 8px;">
            <h3 style="color: #856404; margin-bottom: 1rem;">Action Required</h3>
            <p style="margin-bottom: 1.5rem;">This project needs modifications. Please review the admin comments above and edit your project.</p>
            <a href="auth.php?action=edit_project&id=<?= $project['id'] ?>" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">Edit Project</a>
        </div>
    <?php endif; ?>
    
    <?php if ($project['status'] == 'approved' && isLoggedIn()): ?>
        <div class="vote-actions">
            <?php if ($voting_closed): ?>
                <p style="color: #e74c3c; font-weight: bold;">Voting period closed</p>
                <?php if ($has_voted): ?>
                    <p>You voted for this project</p>
                <?php endif; ?>
            <?php elseif ($has_voted): ?>
                <button onclick="vote(<?= $project['id'] ?>, 'unvote')" class="btn-warning">Withdraw Vote</button>
            <?php else: ?>
                <button onclick="vote(<?= $project['id'] ?>, 'vote')" class="btn-primary">Vote</button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function vote(projectId, action) {
    const button = event.target;
    const voteActions = button.closest('.vote-actions');
    const voteCountElement = document.getElementById('vote-count');
    
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
            if (voteCountElement && data.vote_count !== undefined) {
                voteCountElement.textContent = data.vote_count;
            }
            
            if (action === 'vote') {
                voteActions.innerHTML = '<button onclick="vote(' + projectId + ', \'unvote\')" class="btn-warning">Withdraw Vote</button>';
            } else {
                voteActions.innerHTML = '<button onclick="vote(' + projectId + ', \'vote\')" class="btn-primary">Vote</button>';
            }
        } else {
            alert(data.message);
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