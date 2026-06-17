<?php
$pageTitle = 'Login';
require_once 'includes/header.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($user['role'] === 'admin' ? SITE_URL . '/admin/index.php' : SITE_URL . '/index.php');
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>🌿 Welcome Back</h2>
        <p>Log in to your GreenGrocer account</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom:16px;border-radius:6px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-primary form-full-btn">Log In</button>
        </form>

        <div class="auth-switch">
            Don't have an account? <a href="<?= SITE_URL ?>/register.php">Create one</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
