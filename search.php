<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Search Results';
$error = '';
$results = [];

// Process search
$search_query = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$search_type = isset($_GET['type']) ? clean_input($_GET['type']) : 'all';

if (!empty($search_query)) {
    try {
        // Pagination
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = (int)(get_setting($conn, 'postsPerPage') ?? 20);
        $offset = ($page - 1) * $perPage;
        
        // Prepare search query
        $search_term = "%{$search_query}%";
        
        // Topics search
        if ($search_type == 'all' || $search_type == 'topics') {
            $topicsQuery = "
                SELECT t.id, t.title, t.content as snippet, t.created_at, 
                       f.id as forum_id, f.name as forum_name, 
                       u.id as author_id, u.username as author_name,
                       'topic' as result_type
                FROM topics t
                JOIN forums f ON t.forum_id = f.id
                JOIN users u ON t.author_id = u.id
                WHERE (t.title LIKE ? OR t.content LIKE ?)
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ";
              $stmt = $conn->prepare($topicsQuery);
            // Explicitly bind parameters with proper types
            $stmt->bindParam(1, $search_term, PDO::PARAM_STR);
            $stmt->bindParam(2, $search_term, PDO::PARAM_STR);
            $stmt->bindParam(3, $perPage, PDO::PARAM_INT);
            $stmt->bindParam(4, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $topicResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the topic results
            foreach ($topicResults as &$result) {
                $result['snippet'] = safe_substr(strip_tags($result['snippet']), 0, 200) . '...';
                $result['url'] = "topic.php?id={$result['id']}";
                $results[] = $result;
            }
        }
        
        // Posts search (if no results from topics or if specifically searching posts)
        if (($search_type == 'all' && empty($results)) || $search_type == 'posts') {
            $postsQuery = "
                SELECT p.id, p.content as snippet, p.post_date as created_at, 
                       t.id as topic_id, t.title,
                       f.id as forum_id, f.name as forum_name,
                       u.id as author_id, u.username as author_name,
                       'post' as result_type
                FROM posts p
                JOIN topics t ON p.topic_id = t.id
                JOIN forums f ON t.forum_id = f.id
                JOIN users u ON p.author_id = u.id
                WHERE p.content LIKE ?
                ORDER BY p.post_date DESC
                LIMIT ? OFFSET ?
            ";
              $stmt = $conn->prepare($postsQuery);
            // Explicitly bind parameters with proper types
            $stmt->bindParam(1, $search_term, PDO::PARAM_STR);
            $stmt->bindParam(2, $perPage, PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $postResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the post results
            foreach ($postResults as &$result) {
                $result['snippet'] = safe_substr(strip_tags($result['snippet']), 0, 200) . '...';
                $result['url'] = "topic.php?id={$result['topic_id']}#post-{$result['id']}";
                $results[] = $result;
            }
        }
        
        // Count total results for pagination
        if ($search_type == 'all' || $search_type == 'topics') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM topics WHERE title LIKE ? OR content LIKE ?");
            $stmt->execute([$search_term, $search_term]);
            $totalTopics = $stmt->fetchColumn();
        } else {
            $totalTopics = 0;
        }
        
        if ($search_type == 'all' || $search_type == 'posts') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE content LIKE ?");
            $stmt->execute([$search_term]);
            $totalPosts = $stmt->fetchColumn();
        } else {
            $totalPosts = 0;
        }
        
        $totalResults = $totalTopics + $totalPosts;
        $totalPages = ceil($totalResults / $perPage);
        
    } catch (PDOException $e) {
        $error = 'Search error: ' . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <h1>Search</h1>
    
    <form action="search.php" method="get" class="mb-4">
        <div class="input-group">
            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search for..." required>
            <select name="type" class="form-select">
                <option value="all" <?= $search_type == 'all' ? 'selected' : '' ?>>All</option>
                <option value="topics" <?= $search_type == 'topics' ? 'selected' : '' ?>>Topics</option>
                <option value="posts" <?= $search_type == 'posts' ? 'selected' : '' ?>>Posts</option>
            </select>
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif (!empty($search_query)): ?>
        <h2>Results for "<?= htmlspecialchars($search_query) ?>"</h2>
        
        <?php if (empty($results)): ?>
            <div class="alert alert-info">No results found.</div>
        <?php else: ?>
            <p>Found <?= $totalResults ?> result(s).</p>
            
            <div class="list-group">
                <?php foreach ($results as $result): ?>
                    <a href="<?= $result['url'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1"><?= htmlspecialchars($result['title']) ?></h5>
                            <small><?= format_date($result['created_at']) ?></small>
                        </div>
                        <p class="mb-1"><?= $result['snippet'] ?></p>
                        <small>
                            By <?= htmlspecialchars($result['author_name']) ?> in 
                            <?= htmlspecialchars($result['forum_name']) ?> 
                            (<?= ucfirst($result['result_type']) ?>)
                        </small>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&type=<?= $search_type ?>&page=<?= $page - 1 ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&type=<?= $search_type ?>&page=<?= $i ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&type=<?= $search_type ?>&page=<?= $page + 1 ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
