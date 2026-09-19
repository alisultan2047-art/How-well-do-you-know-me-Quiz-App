<?php
/**
 * Admin Authentication Guard & Common Header Utilities
 */
require_once dirname(__DIR__) . '/config/database.php';

// Check if admin is authenticated
if (empty($_SESSION['admin_user_id'])) {
    // Remember target URL
    $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
    redirect('login.php');
}

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$adminEmail = $_SESSION['admin_email'] ?? '';

/**
 * Renders standard admin navigation sidebar
 */
function render_admin_nav(string $activePage = 'dashboard'): void {
    global $adminUsername;
    $items = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '📊', 'url' => 'index.php'],
        'responses' => ['label' => 'Responses', 'icon' => '👥', 'url' => 'responses.php'],
        'questions' => ['label' => 'Questions', 'icon' => '❓', 'url' => 'questions.php'],
        'settings'  => ['label' => 'Settings & Deploy', 'icon' => '⚙️', 'url' => 'settings.php'],
    ];
    ?>
    <aside class="admin-sidebar">
      <div class="admin-brand">
        <div class="brand-icon">⚡</div>
        <div>
          <strong style="color: #FFFFFF; font-size: 1rem; display: block;">Ali's Admin</strong>
          <small style="color: var(--text-muted); font-size: 0.8rem;"><?= h($adminUsername) ?></small>
        </div>
      </div>

      <nav class="admin-nav">
        <?php foreach ($items as $key => $item): ?>
          <a 
            href="<?= $item['url'] ?>" 
            class="admin-nav-link <?= $activePage === $key ? 'active' : '' ?>"
          >
            <span><?= $item['icon'] ?></span>
            <span><?= $item['label'] ?></span>
          </a>
        <?php endforeach; ?>

        <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border-subtle);">
          <a href="../index.php" target="_blank" class="admin-nav-link">
            <span>🌐</span>
            <span>View Public Quiz ↗</span>
          </a>
          <a href="logout.php" class="admin-nav-link" style="color: #F87171;">
            <span>🚪</span>
            <span>Logout</span>
          </a>
        </div>
      </nav>
    </aside>
    <?php
}
