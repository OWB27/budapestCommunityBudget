<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($action == 'vote' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit();
    }
    
    $project_id = intval($_POST['project_id'] ?? 0);
    $vote_action = $_POST['vote_action'] ?? '';
    
    if ($project_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit();
    }
    
    $project = getProjectById($project_id);
    
    if (!$project || $project['status'] != 'approved') {
        echo json_encode(['success' => false, 'message' => 'Project not published']);
        exit();
    }
    
    $approved_time = strtotime($project['approved']);
    $two_weeks_later = $approved_time + (14 * 24 * 60 * 60);
    if (time() > $two_weeks_later) {
        echo json_encode(['success' => false, 'message' => 'Voting period closed']);
        exit();
    }
    
    $user_id = getUserId();
    $votes = readJSON(VOTES_FILE);
    
    if ($vote_action == 'vote') {
        foreach ($votes as $vote) {
            if ($vote['user_id'] == $user_id && $vote['project_id'] == $project_id) {
                echo json_encode(['success' => false, 'message' => 'Already voted']);
                exit();
            }
        }
        
        $category_votes = 0;
        foreach ($votes as $vote) {
            if ($vote['user_id'] == $user_id) {
                $voted_project = getProjectById($vote['project_id']);
                if ($voted_project && $voted_project['category'] == $project['category']) {
                    $category_votes++;
                }
            }
        }
        
        if ($category_votes >= 3) {
            echo json_encode(['success' => false, 'message' => 'Maximum 3 votes per category']);
            exit();
        }
        
        $votes[] = [
            'id' => getNextId(VOTES_FILE),
            'user_id' => $user_id,
            'project_id' => $project_id,
            'voted_at' => date('Y-m-d H:i:s')
        ];
        
        writeJSON(VOTES_FILE, $votes);
        
        $new_vote_count = count(array_filter($votes, function($v) use ($project_id) {
            return $v['project_id'] == $project_id;
        }));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Vote added successfully!',
            'vote_count' => $new_vote_count,
            'remaining_votes' => 3 - $category_votes - 1
        ]);
        
    } elseif ($vote_action == 'unvote') {
        $vote_found = false;
        $new_votes = array_filter($votes, function($vote) use ($user_id, $project_id, &$vote_found) {
            $is_match = ($vote['user_id'] == $user_id && $vote['project_id'] == $project_id);
            if ($is_match) $vote_found = true;
            return !$is_match;
        });
        
        if (!$vote_found) {
            echo json_encode(['success' => false, 'message' => 'Vote not found']);
            exit();
        }
        
        writeJSON(VOTES_FILE, array_values($new_votes));
        
        $new_vote_count = count(array_filter($new_votes, function($v) use ($project_id) {
            return $v['project_id'] == $project_id;
        }));
        
        $category_votes = 0;
        foreach ($new_votes as $vote) {
            if ($vote['user_id'] == $user_id) {
                $voted_project = getProjectById($vote['project_id']);
                if ($voted_project && $voted_project['category'] == $project['category']) {
                    $category_votes++;
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Vote withdrawn successfully!',
            'vote_count' => $new_vote_count,
            'remaining_votes' => 3 - $category_votes
        ]);
    }
    
    exit();
}

if ($action == 'approve' && isAdmin() && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    
    if ($project_id > 0) {
        $projects = readJSON(PROJECTS_FILE);
        $found = false;
        
        foreach ($projects as &$project) {
            if ($project['id'] == $project_id) {
                $project['status'] = 'approved';
                $project['approved'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($project);
        
        if ($found) {
            writeJSON(PROJECTS_FILE, $projects);
            $_SESSION['success_message'] = "Project approved and published successfully!";
        } else {
            $_SESSION['error_message'] = "Project not found";
        }
    }
    
    redirect('project.php?id=' . $project_id);
}

if ($action == 'reject' && isAdmin() && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    
    if ($project_id > 0) {
        $projects = readJSON(PROJECTS_FILE);
        
        foreach ($projects as &$project) {
            if ($project['id'] == $project_id) {
                $project['status'] = 'rejected';
                break;
            }
        }
        unset($project);
        
        writeJSON(PROJECTS_FILE, $projects);
        $_SESSION['success_message'] = "Project rejected";
    }
    
    redirect('project.php?id=' . $project_id);
}

if ($action == 'rework' && isAdmin() && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $project_id = intval($_POST['project_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($project_id > 0 && !empty($comment)) {
        $projects = readJSON(PROJECTS_FILE);
        
        foreach ($projects as &$project) {
            if ($project['id'] == $project_id) {
                $project['status'] = 'rework';
                break;
            }
        }
        unset($project);
        
        writeJSON(PROJECTS_FILE, $projects);
        
        $comments = readJSON(COMMENTS_FILE);
        $comments[] = [
            'id' => getNextId(COMMENTS_FILE),
            'project_id' => $project_id,
            'admin_id' => getUserId(),
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s')
        ];
        writeJSON(COMMENTS_FILE, $comments);
        
        $_SESSION['success_message'] = "Project sent back for rework";
    }
    
    redirect('project.php?id=' . $project_id);
}

redirect('index.php');
?>