<?php
require_once 'config.php';

$action = $_GET['action'] ?? 'login';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $errors[] = "Please fill all fields";
        } else {
            $user = getUserByUsername($username);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                redirect('index.php');
            } else {
                $errors[] = "Invalid username or password";
            }
        }
    }
    
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        
        if (strpos($username, ' ') !== false) $errors[] = "Username cannot contain spaces";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (strlen($password) < 8 || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) 
            $errors[] = "Password: min 8 chars, uppercase, lowercase, and numbers";
        if ($password !== $password2) $errors[] = "Passwords do not match";
        if (getUserByUsername($username)) $errors[] = "Username already exists";
        
        if (empty($errors)) {
            $users = readJSON(USERS_FILE);
            $users[] = [
                'id' => getNextId(USERS_FILE),
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'is_admin' => false
            ];
            writeJSON(USERS_FILE, $users);
            $success = "Registration successful! Please login.";
            $action = 'login';
        }
    }
    
    if (isset($_POST['submit_project']) && isLoggedIn()) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = $_POST['category'] ?? '';
        $postal_code = trim($_POST['postal_code']);
        $image = trim($_POST['image']);
        
        if (strlen($title) < 10) $errors[] = "Title must be at least 10 characters";
        if (strlen($description) < 150) $errors[] = "Description must be at least 150 characters";
        if (!in_array($category, $categories)) $errors[] = "Invalid category";
        
        if (!preg_match('/^\d{4}$/', $postal_code) || intval($postal_code) < 1000) {
            $errors[] = "Invalid postal code";
        } else if ($postal_code != '1007') {
            $first = substr($postal_code, 0, 1);
            $district = substr($postal_code, 1, 2);
            $last = substr($postal_code, 3, 1);
            if ($first != '1' || intval($district) < 1 || intval($district) > 23 || $last == '0')
                $errors[] = "Invalid Budapest postal code";
        }
        
        if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) $errors[] = "Invalid image URL";
        
        if (empty($errors)) {
            $projects = readJSON(PROJECTS_FILE);
            $new_project = [
                'id' => getNextId(PROJECTS_FILE),
                'status' => 'pending',
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'postal_code' => $postal_code,
                'image' => $image,
                'owner' => getUserId(),
                'submitted' => date('Y-m-d H:i:s'),
                'approved' => null
            ];
            $projects[] = $new_project;
            writeJSON(PROJECTS_FILE, $projects);
            
            $_SESSION['success_message'] = "Project submitted successfully! Waiting for admin review.";
            redirect('projects-own.php');
        }
    }
    
    if (isset($_POST['edit_project']) && isLoggedIn()) {
        $project_id = intval($_POST['project_id']);
        $project = getProjectById($project_id);
        
        if ($project && $project['owner'] == getUserId()) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = $_POST['category'] ?? '';
            $postal_code = trim($_POST['postal_code']);
            $image = trim($_POST['image']);
            
            if (strlen($title) < 10) $errors[] = "Title must be at least 10 characters";
            if (strlen($description) < 150) $errors[] = "Description must be at least 150 characters";
            if (!in_array($category, $categories)) $errors[] = "Invalid category";
            
            if (!preg_match('/^\d{4}$/', $postal_code) || intval($postal_code) < 1000) {
                $errors[] = "Invalid postal code";
            } else if ($postal_code != '1007') {
                $first = substr($postal_code, 0, 1);
                $district = substr($postal_code, 1, 2);
                $last = substr($postal_code, 3, 1);
                if ($first != '1' || intval($district) < 1 || intval($district) > 23 || $last == '0')
                    $errors[] = "Invalid Budapest postal code";
            }
            
            if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL)) $errors[] = "Invalid image URL";
            
            if (empty($errors)) {
                updateProject($project_id, [
                    'title' => $title,
                    'description' => $description,
                    'category' => $category,
                    'postal_code' => $postal_code,
                    'image' => $image,
                    'status' => 'pending'
                ]);
                $_SESSION['success_message'] = "Project updated and resubmitted!";
                redirect('projects-own.php');
            }
        }
    }
}

if ($action == 'logout') {
    session_destroy();
    redirect('index.php');
}

if ($action == 'new_project' && !isLoggedIn()) {
    redirect('auth.php');
}

if ($action == 'edit_project' && !isLoggedIn()) {
    redirect('auth.php');
}

if ($action == 'new_project' && isLoggedIn()) {
    renderHeader('Submit New Project');
    ?>
    <div class="form-container">
        <h2>Submit New Project</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Project Title: *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                <small>At least 10 characters</small>
            </div>
            <div class="form-group">
                <label>Description: *</label>
                <textarea name="description" rows="6" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <small>At least 150 characters</small>
            </div>
            <div class="form-group">
                <label>Category: *</label>
                <select name="category" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['category'] ?? '') == $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Postal Code: *</label>
                <input type="text" name="postal_code" required value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                <small>4 digits, Budapest format (e.g., 1011, 1007)</small>
            </div>
            <div class="form-group">
                <label>Image URL: (optional)</label>
                <input type="url" name="image" value="<?= htmlspecialchars($_POST['image'] ?? '') ?>">
            </div>
            <button type="submit" name="submit_project" class="btn-primary">Submit Project</button>
            <a href="projects-own.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
    <?php
    renderFooter();
    exit();
}

if ($action == 'edit_project' && isLoggedIn()) {
    $project_id = intval($_GET['id'] ?? 0);
    $project = getProjectById($project_id);
    
    if (!$project || $project['owner'] != getUserId() || $project['status'] != 'rework') {
        redirect('projects-own.php');
    }
    
    renderHeader('Edit Project');
    ?>
    <div class="form-container">
        <h2>Edit Project</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div class="form-group">
                <label>Title: *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? $project['title']) ?>" required>
                <small>At least 10 characters</small>
            </div>
            <div class="form-group">
                <label>Description: *</label>
                <textarea name="description" rows="6" required><?= htmlspecialchars($_POST['description'] ?? $project['description']) ?></textarea>
                <small>At least 150 characters</small>
            </div>
            <div class="form-group">
                <label>Category: *</label>
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (($_POST['category'] ?? $project['category']) == $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Postal Code: *</label>
                <input type="text" name="postal_code" value="<?= htmlspecialchars($_POST['postal_code'] ?? $project['postal_code']) ?>" required>
                <small>4 digits, Budapest format (e.g., 1011, 1007)</small>
            </div>
            <div class="form-group">
                <label>Image URL:</label>
                <input type="url" name="image" value="<?= htmlspecialchars($_POST['image'] ?? $project['image']) ?>">
            </div>
            <button type="submit" name="edit_project" class="btn-primary">Save & Resubmit</button>
            <a href="projects-own.php" class="btn-secondary">Cancel</a>
        </form>
    </div>
    <?php
    renderFooter();
    exit();
}

if (isLoggedIn() && $action == 'login') {
    redirect('index.php');
}

$mode = $_GET['mode'] ?? 'login';

renderHeader('Login / Register');
?>

<div class="form-container">
    <div class="auth-tabs">
        <button class="tab-btn <?= $mode == 'login' ? 'active' : '' ?>" onclick="location.href='auth.php?mode=login'">
            Login
        </button>
        <button class="tab-btn <?= $mode == 'register' ? 'active' : '' ?>" onclick="location.href='auth.php?mode=register'">
            Register
        </button>
    </div>
    
    <?php if ($success): ?>
        <div class="success-box"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    
    <?php if ($mode == 'login'): ?>
        <form method="POST">
            <h2>Login</h2>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn-primary">Login</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <h2>Register</h2>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required>
                <small>No spaces allowed</small>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
                <small>Min 8 chars, uppercase, lowercase, and numbers</small>
            </div>
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="password2" required>
            </div>
            <button type="submit" name="register" class="btn-primary">Register</button>
        </form>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>