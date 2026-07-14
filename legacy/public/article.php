<?php
/**
 * LEGACY CODE — DO NOT DEPLOY.
 *
 * Public article page. Representative of the whole codebase:
 * business logic, data access and HTML rendering in one file.
 */

require_once __DIR__ . '/../includes/db.php';
session_start();

// F-01: request input is concatenated straight into SQL.
$id = $_GET['id'];
$res = q("SELECT * FROM articles WHERE id = $id AND published = 1");
$article = mysqli_fetch_assoc($res);

if (!$article) {
    header('HTTP/1.0 404 Not Found');
    echo 'Not found';
    exit;
}

// F-05: view counter written on every request, no queue, no rate limit.
q("UPDATE articles SET views = views + 1 WHERE id = $id");

// F-02: comment submission — no CSRF token, no validation, no auth check.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = $_POST['author'];
    $body   = $_POST['body'];
    q("INSERT INTO comments (article_id, author, body, created_at)
       VALUES ($id, '$author', '$body', NOW())");
    header('Location: article.php?id=' . $id);
    exit;
}

$comments = q("SELECT * FROM comments WHERE article_id = $id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!-- F-03: title rendered unescaped -->
    <title><?php echo $article['title']; ?></title>
</head>
<body>

<h1><?php echo $article['title']; ?></h1>
<p><small>Views: <?php echo $article['views']; ?></small></p>

<!-- F-03: article body rendered unescaped. Stored HTML executes. -->
<div><?php echo $article['body']; ?></div>

<h2>Comments</h2>
<?php while ($c = mysqli_fetch_assoc($comments)): ?>
    <div class="comment">
        <!-- F-03: comment author and body rendered unescaped -->
        <strong><?php echo $c['author']; ?></strong>
        <p><?php echo $c['body']; ?></p>
    </div>
<?php endwhile; ?>

<h3>Leave a comment</h3>
<form method="post">
    <input type="text" name="author" placeholder="Your name">
    <textarea name="body"></textarea>
    <button type="submit">Send</button>
</form>

</body>
</html>
