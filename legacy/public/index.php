<?php
/**
 * LEGACY CODE — DO NOT DEPLOY.
 *
 * Article list with search and pagination.
 */

require_once __DIR__ . '/../includes/db.php';

// F-01: search term concatenated into a LIKE clause.
$search = isset($_GET['q']) ? $_GET['q'] : '';

// F-12: no pagination — the whole table is loaded on every hit.
// F-13: SELECT * fetches the article body for a listing that shows titles only.
$sql = "SELECT * FROM articles WHERE published = 1";
if ($search !== '') {
    $sql .= " AND title LIKE '%$search%'";
}
$sql .= " ORDER BY created_at DESC";

$articles = q($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Blog</title></head>
<body>
<h1>Articles</h1>

<form method="get">
    <!-- F-03: the search term is echoed back unescaped (reflected XSS) -->
    <input type="text" name="q" value="<?php echo $search; ?>">
    <button type="submit">Search</button>
</form>

<?php while ($a = mysqli_fetch_assoc($articles)): ?>
    <article>
        <h2><a href="article.php?id=<?php echo $a['id']; ?>"><?php echo $a['title']; ?></a></h2>
        <p><?php echo substr(strip_tags($a['body']), 0, 200); ?>...</p>
    </article>
<?php endwhile; ?>

</body>
</html>
