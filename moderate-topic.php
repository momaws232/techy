<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Check if user is admin or moderator
if (!is_admin() && !is_moderator()) {
    $_SESSION['error_message'] = "You don't have permission to moderate topics.";
    header('Location: index.php');
    exit;
}

// Check for required parameters
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header('Location: forums.php');
    exit;
}

$topic_id = (int)$_GET['id'];
$action = $_GET['action'];

try {
    // Get topic info
    $stmt = $conn->prepare("
        SELECT t.*, f.id as forum_id 
        FROM topics t
        JOIN forums f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$topic) {
        $_SESSION['error_message'] = "Topic not found.";
        header('Location: forums.php');
        exit;
    }
    
    // Check if user is moderator for this forum (unless they're an admin)
    if (!is_admin()) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM forum_moderators 
            WHERE forum_id = ? AND user_id = ?
        ");
        $stmt->execute([$topic['forum_id'], $_SESSION['user_id']]);
        $isModerator = $stmt->fetchColumn() > 0;
        
        if (!$isModerator) {
            $_SESSION['error_message'] = "You don't have permission to moderate this forum.";
            header('Location: topic.php?id=' . $topic_id);
            exit;
        }
    }
    
    // Handle different actions
    switch ($action) {
        case 'sticky':
            // Toggle sticky status
            $newStatus = $topic['is_sticky'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE topics SET is_sticky = ? WHERE id = ?");
            $stmt->execute([$newStatus, $topic_id]);
            $_SESSION['success_message'] = $newStatus ? "Topic has been stickied." : "Topic has been unstickied.";
            break;
            
        case 'lock':
            // Toggle locked status
            $newStatus = $topic['is_locked'] ? 0 : 1;
            $stmt = $conn->prepare("UPDATE topics SET is_locked = ? WHERE id = ?");
            $stmt->execute([$newStatus, $topic_id]);
            $_SESSION['success_message'] = $newStatus ? "Topic has been locked." : "Topic has been unlocked.";
            break;
            
        case 'move':
            // If this is a form submission for moving
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_forum_id'])) {
                $new_forum_id = (int)$_POST['new_forum_id'];
                
                // Verify the forum exists
                $stmt = $conn->prepare("SELECT id FROM forums WHERE id = ?");
                $stmt->execute([$new_forum_id]);
                if ($stmt->fetch()) {
                    // Move the topic
                    $stmt = $conn->prepare("UPDATE topics SET forum_id = ? WHERE id = ?");
                    $stmt->execute([$new_forum_id, $topic_id]);
                    
                    $_SESSION['success_message'] = "Topic has been moved successfully.";
                    header('Location: topic.php?id=' . $topic_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = "Invalid destination forum.";
                }
            }
            
            // Display the move form
            $pageTitle = "Move Topic";
            include 'templates/header.php';
            
            // Get available forums for moving
            $stmt = $conn->prepare("SELECT id, name, category FROM forums WHERE status = 'active'");
            $stmt->execute();
            $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group forums by category
            $categorizedForums = [];
            foreach ($forums as $forum) {
                if (!isset($categorizedForums[$forum['category']])) {
                    $categorizedForums[$forum['category']] = [];
                }
                $categorizedForums[$forum['category']][] = $forum;
            }
            ?>
            
            <div class="container mt-4">
                <div class="card">
                    <div class="card-header">
                        <h2>Move Topic: <?= htmlspecialchars($topic['title']) ?></h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="new_forum_id" class="form-label">Select Destination Forum:</label>
                                <select class="form-select" id="new_forum_id" name="new_forum_id" required>
                                    <?php foreach ($categorizedForums as $category => $categoryForums): ?>
                                        <optgroup label="<?= htmlspecialchars($category) ?>">
                                            <?php foreach ($categoryForums as $forum): ?>
                                                <option value="<?= $forum['id'] ?>" <?= $forum['id'] == $topic['forum_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($forum['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3 d-flex justify-content-between">
                                <a href="topic.php?id=<?= $topic_id ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Move Topic</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php
            include 'templates/footer.php';
            exit;
            
        default:
            $_SESSION['error_message'] = "Invalid action.";
            break;
    }
    
    // Redirect back to topic
    header('Location: topic.php?id=' . $topic_id);
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header('Location: topic.php?id=' . $topic_id);
}
?> 