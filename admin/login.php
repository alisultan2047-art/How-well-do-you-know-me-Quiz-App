<?php
require_once dirname(__DIR__) . '/config/database.php';

// If already logged in, go straight to dashboard
if (!empty($_SESSION['admin_user_id'])) {
    redirect('index.php');
}

$errorMessage = '';
$needsSetup = false;

try {
    $db = get_db();
    $cntStmt = $db->query("SELECT COUNT(*) as cnt FROM admin_users");
    $cnt = (int)($cntStmt->fetch()['cnt'] ?? 0);
    if ($cnt === 0) {
        $needsSetup = true;
    }
} catch (Exception $e) {
    // If table check fails, proceed normally
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $errorMessage = "Session expired or invalid security token. Please try again.";
    } else {
        $loginInput = trim($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($loginInput === '' || $password === '') {
            $errorMessage = "Please enter both username/email and password.";
        } else {
            try {
                $db = get_db();
                $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$loginInput, $loginInput]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Password matches! Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);

                    $_SESSION['admin_user_id'] = (int)$user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_email'] = $user['email'];

                    $target = $_SESSION['admin_redirect'] ?? 'index.php';
                    unset($_SESSION['admin_redirect']);
                    redirect($target);
                } else {
                    $errorMessage = "Invalid username/email or password.";
                }
            } catch (Exception $e) {
                error_log("Login query error: " . $e->getMessage());
                $errorMessage = "An error occurred during authentication. Please try again.";
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
  <title>Admin Login | How Well Do You Know Ali?</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="app-wrapper">
    <!-- Simple Header -->
    <header class="site-header">
      <a href="../index.php" class="brand-badge">
        <div class="brand-icon">⚡</div>
        <span>Know Ali</span>
      </a>
      <div class="header-actions">
        <a href="../index.php" class="header-link">← Back to Quiz</a>
      </div>
    </header>

    <main class="container" style="max-width: 440px; margin-top: 40px;">
      <div class="quiz-card">
        <div class="text-center" style="margin-bottom: 24px;">
          <div style="font-size: 2.4rem; margin-bottom: 8px;">🔐</div>
          <h1 style="font-size: 1.6rem; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">
            Admin Portal
          </h1>
          <p class="text-muted" style="font-size: 0.9rem;">
            Private dashboard for Ali Sultan
          </p>
        </div>

        <?php if ($needsSetup): ?>
          <div style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.35); color: #93C5FD; padding: 14px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
            <strong>Initial Setup Required:</strong> No admin account found.<br>
            <a href="setup.php" style="color: #60A5FA; font-weight: 700; text-decoration: underline; margin-top: 6px; display: inline-block;">Click here to create your Admin Account →</a>
          </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
          <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 14px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
            ⚠️ <?= h($errorMessage) ?>
          </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="form-group">
            <label for="username_or_email" class="form-label">Username or Email</label>
            <input 
              type="text" 
              id="username_or_email" 
              name="username_or_email" 
              class="form-input" 
              placeholder="e.g. your username or email" 
              value="<?= h($_POST['username_or_email'] ?? '') ?>"
              required 
              autocomplete="username"
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-input" 
              placeholder="••••••••••••" 
              required 
              autocomplete="current-password"
            >
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-size: 1rem; margin-top: 8px;">
            Sign In to Dashboard 🚀
          </button>
        </form>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-subtle); font-size: 0.82rem; color: var(--text-dim); text-align: center;">
          <p>Protected area for Ali Sultan • Session-authenticated</p>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
