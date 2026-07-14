<?php
/**
 * LEGACY CODE — DO NOT DEPLOY.
 *
 * Authentication, 2010 style.
 */

require_once __DIR__ . '/../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    // F-01: SQL built by concatenation, on the authentication path.
    // F-04: passwords stored as unsalted MD5.
    $hash = md5($password);
    $res  = q("SELECT * FROM users WHERE email = '$email' AND password = '$hash'");
    $user = mysqli_fetch_assoc($res);

    if ($user) {
        // F-06: no session regeneration on privilege change (session fixation).
        // F-07: the role is copied into the session and trusted from then on;
        //        it is never re-checked against the database.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        header('Location: admin.php');
        exit;
    }

    // F-08: no rate limiting, no lockout, no logging of failed attempts.
    $error = 'Wrong email or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h1>Login</h1>
<?php if ($error): ?><p style="color:red"><?php echo $error; ?></p><?php endif; ?>

<!-- F-02: no CSRF token on any form in the application -->
<form method="post">
    <input type="email" name="email" placeholder="Email">
    <input type="password" name="password" placeholder="Password">
    <button type="submit">Log in</button>
</form>
</body>
</html>
