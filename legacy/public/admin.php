<?php
/**
 * LEGACY CODE — DO NOT DEPLOY.
 *
 * Admin panel. Article CRUD + cover image upload.
 */

require_once __DIR__ . '/../includes/db.php';
session_start();

// F-09: the only access check in the whole panel. Any authenticated user
// passes it — role is never verified. Deleting is one query string away.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// F-02 + F-10: destructive action behind a GET request, no CSRF token,
// no confirmation, no soft delete, no audit trail.
if ($action === 'delete') {
    $id = $_GET['id'];
    q("DELETE FROM articles WHERE id = $id");
    header('Location: admin.php');
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $body  = $_POST['body'];
    $id    = isset($_POST['id']) ? $_POST['id'] : '';

    // F-11: cover image upload with no validation whatsoever —
    // no MIME check, no extension allow-list, no size limit,
    // original filename trusted, target directory is web-accessible.
    $cover = '';
    if (!empty($_FILES['cover']['name'])) {
        $cover = $_FILES['cover']['name'];
        move_uploaded_file($_FILES['cover']['tmp_name'], __DIR__ . '/../uploads/' . $cover);
    }

    if ($id) {
        q("UPDATE articles SET title = '$title', body = '$body' WHERE id = $id");
    } else {
        q("INSERT INTO articles (title, body, cover, published, views, created_at)
           VALUES ('$title', '$body', '$cover', 1, 0, NOW())");
    }

    header('Location: admin.php');
    exit;
}

$articles = q("SELECT * FROM articles ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Admin</title></head>
<body>
<h1>Articles</h1>

<table border="1">
<?php while ($a = mysqli_fetch_assoc($articles)): ?>
    <tr>
        <td><?php echo $a['id']; ?></td>
        <!-- F-03: unescaped output, again -->
        <td><?php echo $a['title']; ?></td>
        <td><a href="admin.php?action=delete&id=<?php echo $a['id']; ?>">delete</a></td>
    </tr>
<?php endwhile; ?>
</table>

<h2>New article</h2>
<form method="post" action="admin.php?action=save" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Title">
    <textarea name="body"></textarea>
    <input type="file" name="cover">
    <button type="submit">Save</button>
</form>
</body>
</html>
