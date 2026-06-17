<?php
$pageTitle = 'Register';
require_once 'includes/header.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,'customer')");
            $ins->bind_param("sss", $name, $email, $hash);
            if ($ins->execute()) {
                setFlash('success', 'Account created! Please log in.');
                redirect(SITE_URL . '/login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h2>🌱 Create Account</h2>
        <p>Join GreenGrocer for fresh organic deliveries</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom:16px;border-radius:6px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="Jane Doe"
                    value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com"
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Min. 6 characters">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat password">
            </div>
            <button type="submit" class="btn-primary form-full-btn">Create Account</button>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="<?= SITE_URL ?>/login.php">Log in</a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
