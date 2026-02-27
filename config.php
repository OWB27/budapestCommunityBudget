<?php
session_start();

define('USERS_FILE', 'data/users.json');
define('PROJECTS_FILE', 'data/projects.json');
define('VOTES_FILE', 'data/votes.json');
define('COMMENTS_FILE', 'data/comments.json');

function initDataFiles() {
    if (!file_exists('data')) {
        mkdir('data', 0777, true);
    }
    
    if (!file_exists(USERS_FILE)) {
        $admin = [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@budapest.hu',
            'password' => password_hash('admin', PASSWORD_DEFAULT),
            'is_admin' => true
        ];
        file_put_contents(USERS_FILE, json_encode([$admin], JSON_PRETTY_PRINT));
    }
    
    if (!file_exists(PROJECTS_FILE)) {
        file_put_contents(PROJECTS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
    
    if (!file_exists(VOTES_FILE)) {
        file_put_contents(VOTES_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
    
    if (!file_exists(COMMENTS_FILE)) {
        file_put_contents(COMMENTS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
}

initDataFiles();

function readJSON($file) {
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

function writeJSON($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getUserById($id) {
    $users = readJSON(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] == $id) return $user;
    }
    return null;
}

function getUserByUsername($username) {
    $users = readJSON(USERS_FILE);
    foreach ($users as $user) {
        if ($user['username'] == $username) return $user;
    }
    return null;
}

function getProjectById($id) {
    $projects = readJSON(PROJECTS_FILE);
    foreach ($projects as $project) {
        if ($project['id'] == $id) return $project;
    }
    return null;
}

function getNextId($file) {
    $data = readJSON($file);
    if (empty($data)) return 1;
    $maxId = max(array_column($data, 'id'));
    return $maxId + 1;
}

function updateProject($id, $updates) {
    $projects = readJSON(PROJECTS_FILE);
    $found = false;
    
    foreach ($projects as $key => &$project) {
        if ($project['id'] == $id) {
            foreach ($updates as $field => $value) {
                $project[$field] = $value;
            }
            $found = true;
            break;
        }
    }
    unset($project);
    
    if ($found) {
        writeJSON(PROJECTS_FILE, $projects);
        return true;
    }
    
    return false;
}

$categories = [
    'Local small project',
    'Local large project',
    'Equal opportunity Budapest',
    'Green Budapest'
];

$statuses = [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'rework' => 'Rework'
];

function renderHeader($title = 'Budapest Community Budget') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?></title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header>
            <div class="container">
                <h1>Budapest Community Budget</h1>
                <nav>
                    <a href="index.php">Home</a>
                    <?php if (isLoggedIn()): ?>
                        <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                        <a href="projects-own.php">My Projects</a>
                        <a href="auth.php?action=new_project">Submit Project</a>
                        <?php if (isAdmin()): ?>
                            <a href="projects-admin.php">Admin</a>
                            <a href="statistics.php">Statistics</a>
                        <?php endif; ?>
                        <a href="auth.php?action=logout">Logout</a>
                    <?php else: ?>
                        <a href="auth.php">Login / Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        <main class="container">
    <?php
}

function renderFooter() {
    echo '</main></body></html>';
}
?>