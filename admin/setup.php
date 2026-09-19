<?php
require_once dirname(__DIR__) . '/config/database.php';

$db = get_db();
$adminCount = 0;

try {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM admin_users");
    $adminCount = (int)($stmt->fetch()['cnt'] ?? 0);
} catch (Exception $e) {
    error_log("Admin check error: " . $e->getMessage());
    $adminCount = 0;
}

// If an admin already exists, lock setup completely
if ($adminCount > 0) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Setup Locked | Know Ali Admin</title>
      <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
      <div class="app-wrapper">
        <header class="site-header">
          <a href="../index.php" class="brand-badge">
            <div class="brand-icon">⚡</div>
            <span>Know Ali</span>
          </a>
          <div class="header-actions">
            <a href="login.php" class="header-link">Sign In →</a>
          </div>
        </header>

        <main class="container" style="max-width: 460px; margin-top: 50px;">
          <div class="quiz-card text-center" style="padding: 36px 24px;">
            <div style="font-size: 2.8rem; margin-bottom: 12px;">🔒</div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #FFFFFF; margin-bottom: 8px;">
              Admin Setup Locked
            </h1>
            <p class="text-muted" style="font-size: 0.92rem; line-height: 1.6; margin-bottom: 24px;">
              An administrator account has already been created for this installation. For security reasons, the initial setup wizard is permanently disabled.
            </p>
            <a href="login.php" class="btn btn-primary btn-block">
              Proceed to Login 🚀
            </a>
          </div>
        </main>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $errorMessage = "Security token validation failed. Please refresh the page.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $errorMessage = "All fields are required.";
        } elseif (strlen($username) < 3 || strlen($username) > 40 || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $errorMessage = "Username must be between 3 and 40 alphanumeric characters (letters, numbers, underscores).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $errorMessage = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirmPassword) {
            $errorMessage = "Passwords do not match. Please re-type them carefully.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $db->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
                $ins->execute([$username, $email, $hash]);
                $newId = (int)$db->lastInsertId();

                // Session fixation prevention
                session_regenerate_id(true);
                $_SESSION['admin_user_id'] = $newId;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;

                redirect('index.php?setup=complete');
            } catch (Exception $e) {
                error_log("Admin setup error: " . $e->getMessage());
                $errorMessage = "Failed to create administrator account. Please check database permissions.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Initial Admin Setup | How Well Do You Know Ali?</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="app-wrapper">
    <header class="site-header">
      <a href="../index.php" class="brand-badge">
        <div class="brand-icon">⚡</div>
        <span>Know Ali</span>
      </a>
      <div class="header-actions">
        <a href="../index.php" class="header-link">← Back to Quiz</a>
      </div>
    </header>

    <main class="container" style="max-width: 480px; margin-top: 36px;">
      <div class="quiz-card">
        <div class="text-center" style="margin-bottom: 24px;">
          <div style="font-size: 2.6rem; margin-bottom: 8px;">🛡️</div>
          <h1 style="font-size: 1.6rem; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">
            Create Admin Account
          </h1>
          <p class="text-muted" style="font-size: 0.9rem;">
            Set up your private credentials for Ali Sultan's quiz manager
          </p>
        </div>

        <?php if ($errorMessage !== ''): ?>
          <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
            ⚠️ <?= h($errorMessage) ?>
          </div>
        <?php endif; ?>

        <form action="setup.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="form-group">
            <label for="username" class="form-label">Admin Username</label>
            <input 
              type="text" 
              id="username" 
              name="username" 
              class="form-input" 
              placeholder="e.g. ali" 
              value="<?= h($_POST['username'] ?? 'ali') ?>"
              required 
              autocomplete="username"
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Admin Email</label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-input" 
              placeholder="e.g. alisultan2047@gmail.com" 
              value="<?= h($_POST['email'] ?? 'alisultan2047@gmail.com') ?>"
              required 
              autocomplete="email"
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-input" 
              placeholder="Min. 8 characters" 
              required 
              minlength="8"
              autocomplete="new-password"
            >
          </div>

          <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input 
              type="password" 
              id="confirm_password" 
              name="confirm_password" 
              class="form-input" 
              placeholder="Re-type password" 
              required 
              minlength="8"
              autocomplete="new-password"
            >
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1rem; margin-top: 8px;">
            Complete Setup &amp; Sign In 🚀
          </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 0.82rem; color: var(--text-dim);">
          🔒 Passwords are cryptographically hashed using standard Bcrypt.
        </div>
      </div>
    </main>
  </div>
</body>
</html>
