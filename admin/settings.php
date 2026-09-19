<?php
require_once __DIR__ . '/auth.php';

$db = get_db();
$successMsg = '';
$errorMsg = '';

// Password Change Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $errorMsg = "Security token validation failed. Please refresh.";
    } else {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 8) {
            $errorMsg = "New password must be at least 8 characters long.";
        } elseif ($newPass !== $confirmPass) {
            $errorMsg = "New passwords do not match.";
        } else {
            try {
                // Verify current password
                $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
                $stmt->execute([$_SESSION['admin_user_id']]);
                $user = $stmt->fetch();

                if ($user && password_verify($currentPass, $user['password_hash'])) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $upStmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
                    $upStmt->execute([$newHash, $_SESSION['admin_user_id']]);
                    $successMsg = "Password successfully updated! Keep your new password in a safe place.";
                } else {
                    $errorMsg = "Current password is incorrect.";
                }
            } catch (Exception $e) {
                $errorMsg = "Failed to update password. Please try again.";
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
  <title>Settings &amp; Deploy | Know Ali Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <?php render_admin_nav('settings'); ?>

    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em;">
            Settings &amp; InfinityFree Deployment
          </h1>
          <p class="text-muted" style="font-size: 0.95rem;">
            Manage security credentials and export ready-to-upload files for InfinityFree hosting
          </p>
        </div>
      </div>

      <?php if ($successMsg !== ''): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6EE7B7; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
          ✓ <?= h($successMsg) ?>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg !== ''): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
          ⚠️ <?= h($errorMsg) ?>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
        <!-- Card 1: Change Password -->
        <div class="quiz-card" style="padding: 24px; border-radius: var(--radius-lg);">
          <h2 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">
            🔐 Admin Security
          </h2>
          <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 20px;">
            Update your admin login password (hashed with bcrypt).
          </p>

          <form action="settings.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
              <label for="current_password" class="form-label">Current Password</label>
              <input type="password" name="current_password" id="current_password" class="form-input" required autocomplete="current-password">
            </div>

            <div class="form-group">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" name="new_password" id="new_password" class="form-input" placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
            </div>

            <div class="form-group">
              <label for="confirm_password" class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" id="confirm_password" class="form-input" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
              Update Password
            </button>
          </form>
        </div>

        <!-- Card 2: InfinityFree Deployment Center -->
        <div class="quiz-card" style="padding: 24px; border-radius: var(--radius-lg);">
          <h2 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">
            🚀 InfinityFree Hosting Package
          </h2>
          <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 20px;">
            Download the production files ready to upload to your free shared hosting account.
          </p>

          <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
            <a href="download-zip.php" class="btn btn-success btn-block" style="padding: 14px;">
              <span>📦 Download InfinityFree ZIP</span>
            </a>
            <a href="../database.sql" download="database.sql" class="btn btn-secondary btn-block">
              <span>📄 Download database.sql</span>
            </a>
          </div>

          <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-subtle); padding: 16px; border-radius: var(--radius-md); font-size: 0.85rem; color: var(--text-muted);">
            <strong style="color: #FFFFFF; display: block; margin-bottom: 8px;">Quick Deployment Summary:</strong>
            <ol style="padding-left: 18px; line-height: 1.7;">
              <li>Create a MySQL Database in InfinityFree VistaPanel.</li>
              <li>Open phpMyAdmin and import <code>database.sql</code>.</li>
              <li>Copy <code>config/database.example.php</code> to <code>config/database.php</code> and fill in your DB credentials.</li>
              <li>Upload all files into your domain's <code>htdocs/</code> folder via FTP or Monsta File Manager.</li>
              <li>Open your website domain and start sharing!</li>
            </ol>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
