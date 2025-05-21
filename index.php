<?php
// Error handling for database connection
try {
    require_once 'config/database.php';
    require_once 'config/functions.php';
    require_once 'config/likes_helpers.php'; // Include likes helper functions

    // Get site title from settings - use $pdo instead of $conn
    $siteTitle = get_setting($pdo, 'siteTitle');

    // Make sure $siteTitle is never null
    $siteTitle = $siteTitle ?? 'Tech Forum'; // Provide default value

    // Get all forum categories - use $pdo instead of $conn
    $stmt = $pdo->query("SELECT DISTINCT category FROM forums WHERE status = 'active'");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get latest topics - use $pdo instead of $conn
    $latestTopics = $pdo->query("
        SELECT t.id, t.title, t.views, t.created_at, f.name as forum_name, f.id as forum_id, u.username as author 
        FROM topics t
        JOIN forums f ON t.forum_id = f.id
        JOIN users u ON t.author_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Get news - use $pdo instead of $conn
    $news = $pdo->query("
        SELECT n.id, n.title, n.summary, n.publish_date, u.username as author
        FROM news n
        JOIN users u ON n.author_id = u.id
        WHERE n.status = 'published'
        ORDER BY n.publish_date DESC
        LIMIT 3
    ")->fetchAll();
    
    // Get popular posts (with most likes)
    $popularPosts = get_most_liked_posts($pdo, 3);

    include 'templates/header.php';
} catch (Exception $e) {
    // Show a user-friendly error message
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container mt-5">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Application Error</h4>
                <p>The application could not connect to the database. Please check your configuration.</p>
                <hr>
                <p class="mb-0">Error details: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>
            <div class="card">
                <div class="card-header">Troubleshooting</div>
                <div class="card-body">
                    <ol>
                        <li>Make sure XAMPP/WAMP is running</li>
                        <li>Check database credentials in config/database.php</li>
                        <li>Verify database and tables exist</li>
                    </ol>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
?>

<div class="container">
    <div class="welcome-banner">
        <h1>Welcome to <?= htmlspecialchars($siteTitle ?? '') ?></h1>
        <p>Your ultimate tech resource for discussions, news, and community.</p>
    </div>
    
    <?php if (isset($_SESSION['login_success'])): ?>
    <!-- Welcome back notification -->
    <div class="notify-card notify-green notify-fade-out" id="loginSuccessNotification">
        <div class="notify-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification('loginSuccessNotification')">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </div>
        <div class="notify-card-body">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
                <p>You've successfully logged in. Enjoy your experience on our forum.</p>
            </div>
        </div>
        <div class="notify-progress">
            <a href="#" class="notify-btn-first" onclick="closeNotification('loginSuccessNotification')">Dismiss</a>
        </div>
    </div>
    <?php 
        // Clear the login success message after displaying it
        unset($_SESSION['login_success']);
    endif; 
    ?>
    
    <?php if (isset($_SESSION['post_success'])): ?>
    <!-- Post success notification -->
    <div class="notify-card notify-blue notify-fade-out" id="postSuccessNotification">
        <div class="notify-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification('postSuccessNotification')">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </div>
        <div class="notify-card-body">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm.53 5.47a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72v5.69a.75.75 0 0 0 1.5 0v-5.69l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3>Post published successfully!</h3>
                <p><?= htmlspecialchars($_SESSION['post_success']) ?></p>
            </div>
        </div>
        <div class="notify-progress">
            <a href="#" class="notify-btn-first" onclick="closeNotification('postSuccessNotification')">Dismiss</a>
        </div>
    </div>
    <?php 
        // Clear the post success message
        unset($_SESSION['post_success']);
    endif; 
    ?>
    
    <?php if (isset($_SESSION['profile_updated'])): ?>
    <!-- Profile update notification -->
    <div class="notify-card notify-green notify-fade-out" id="profileUpdateNotification">
        <div class="notify-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification('profileUpdateNotification')">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </div>
        <div class="notify-card-body">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3>Profile updated!</h3>
                <p>Your profile information has been successfully updated.</p>
            </div>
        </div>
        <div class="notify-progress">
            <a href="profile.php" class="notify-btn-first">View Profile</a>
            <a href="#" class="notify-btn-second" onclick="closeNotification('profileUpdateNotification')">Dismiss</a>
        </div>
    </div>
    <?php 
        // Clear the profile update message
        unset($_SESSION['profile_updated']);
    endif; 
    ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <!-- Error notification -->
    <div class="notify-card notify-red notify-fade-out" id="errorNotification">
        <div class="notify-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="notify-close" onclick="closeNotification('errorNotification')">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </div>
        <div class="notify-card-body">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="notify-icon">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3>Oops! Something went wrong</h3>
                <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
            </div>
        </div>
        <div class="notify-progress">
            <a href="#" class="notify-btn-first" onclick="closeNotification('errorNotification')">Dismiss</a>
        </div>
    </div>
    <?php 
        // Clear the error message
        unset($_SESSION['error_message']);
    endif; 
    ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Forum Categories</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($categories as $category): ?>
                        <div class="category">
                            <h3><?= htmlspecialchars(ucfirst($category)) ?></h3>
                            <div class="forums-list">
                                <?php
                                $stmt = $pdo->prepare("SELECT id, name, description, icon FROM forums WHERE category = ? AND status = 'active'");
                                $stmt->execute([$category]);
                                $forums = $stmt->fetchAll();
                                
                                foreach ($forums as $forum):
                                ?>
                                <div class="forum">
                                    <div class="forum-icon">
                                        <i class="<?= htmlspecialchars($forum['icon']) ?>"></i>
                                    </div>
                                    <div class="forum-info">
                                        <h4><a href="forum.php?id=<?= htmlspecialchars($forum['id']) ?>"><?= htmlspecialchars($forum['name']) ?></a></h4>
                                        <p><?= htmlspecialchars($forum['description']) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Latest Topics</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($latestTopics as $topic): ?>
                    <div class="latest-topic">
                        <h5><a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></a></h5>
                        <div class="topic-meta">
                            <span>by <?= htmlspecialchars($topic['author']) ?></span>
                            <span>in <a href="forum.php?id=<?= $topic['forum_id'] ?>"><?= htmlspecialchars($topic['forum_name']) ?></a></span>
                            <span><?= format_date($topic['created_at']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Latest News</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($news as $article): ?>
                    <div class="news-item">
                        <h5><a href="news.php?id=<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></a></h5>
                        <p><?= htmlspecialchars($article['summary']) ?></p>
                        <div class="news-meta">
                            <span>by <?= htmlspecialchars($article['author']) ?></span>
                            <span><?= format_date($article['publish_date']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!empty($popularPosts)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Popular Posts <i class="fas fa-fire text-danger"></i></h2>
                </div>
                <div class="card-body">
                    <?php foreach ($popularPosts as $post): ?>
                    <div class="popular-post">
                        <h5><a href="topic.php?id=<?= $post['topic_id'] ?>#post-<?= $post['id'] ?>"><?= htmlspecialchars($post['topic_title']) ?></a></h5>
                        <div class="post-excerpt">
                            <?= substr(strip_tags($post['content']), 0, 100) ?>...
                        </div>
                        <div class="post-meta">
                            <span>by <?= htmlspecialchars($post['author_name']) ?></span>
                            <span><i class="fas fa-thumbs-up"></i> <?= $post['likes_count'] ?> likes</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
