<?php
require_once __DIR__ . '/config/database.php';

// Fetch question count from database for dynamic badge
$totalQuestions = 10;
try {
    $db = get_db();
    $stmt = $db->query("SELECT COUNT(*) as count FROM questions WHERE is_active = 1");
    $totalQuestions = (int)($stmt->fetch()['count'] ?? 10);
} catch (Exception $e) {
    // Graceful fallback
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>How Well Do You Know Ali? 🤔 | Personal Quiz</title>
  <meta name="description" content="Think you know Ali Sultan? Take this 10-question personal challenge and see how well you really know his favorites, tech passions, and habits!">
  
  <!-- Open Graph -->
  <meta property="og:title" content="How Well Do You Know Ali? 🤔">
  <meta property="og:description" content="Think you know Ali Sultan? Take the quiz and test your friendship score!">
  <meta property="og:type" content="website">
  
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="app-wrapper">
    <!-- Header -->
    <header class="site-header">
      <a href="index.php" class="brand-badge">
        <div class="brand-icon">⚡</div>
        <span>Know Ali</span>
      </a>
      <div class="header-actions">
        <a href="admin/login.php" class="header-link">Admin 🔐</a>
      </div>
    </header>

    <!-- Main Container -->
    <main class="container">
      <div class="hero-header">
        <div class="hero-pill">
          <span class="hero-pill-dot"></span>
          <span>Personal Friendship Quiz • By Ali Sultan</span>
        </div>
        <h1 class="hero-title">How Well Do You Know <span class="highlight">Ali</span>? 🤔</h1>
        <p class="hero-subtitle">“Think you know me? Let's find out!”</p>
      </div>

      <!-- Main Card -->
      <div class="quiz-card">
        <?php if ($error === 'missing_name'): ?>
          <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 0.92rem;">
            ⚠️ Please enter your name so Ali knows who took the quiz!
          </div>
        <?php elseif ($error === 'no_questions'): ?>
          <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); color: #FCD34D; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 0.92rem;">
            ⚠️ No active quiz questions found. Please add questions in the admin panel.
          </div>
        <?php endif; ?>

        <form action="quiz.php" method="GET" id="startForm">
          <div class="form-group">
            <label for="friendName" class="form-label">
              What's your name? <span style="color: var(--primary-light);">*</span>
            </label>
            <input 
              type="text" 
              id="friendName" 
              name="friend_name" 
              class="form-input" 
              placeholder="e.g. Ahmed, Sarah, Hassan..." 
              required 
              maxlength="60"
              autocomplete="name"
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="nickname" class="form-label">
              Nickname or Insider Joke <small>(Optional)</small>
            </label>
            <input 
              type="text" 
              id="nickname" 
              name="nickname" 
              class="form-input" 
              placeholder="e.g. Captain Code, Bro, AI Buddy" 
              maxlength="40"
            >
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="padding: 16px; font-size: 1.05rem; margin-top: 8px;">
            Start Quiz 🚀
          </button>
        </form>

        <!-- Feature Pills -->
        <div class="features-grid">
          <div class="feature-item">
            <strong><?= $totalQuestions ?> Questions</strong>
            <span>Curated about Ali</span>
          </div>
          <div class="feature-item">
            <strong>Server Scored</strong>
            <span>Verified by PHP</span>
          </div>
          <div class="feature-item">
            <strong>Challenge</strong>
            <span>Share with friends</span>
          </div>
        </div>

        <p class="text-center text-muted" style="font-size: 0.82rem; margin-top: 20px;">
          No sign-up or registration needed. Your score will be saved securely for Ali to see!
        </p>
      </div>

      <!-- Footer Info -->
      <footer style="text-align: center; margin-top: 40px; color: var(--text-dim); font-size: 0.85rem;">
        <p>Built with modern PHP 8 &amp; MySQL • Crafted for <strong>Ali Sultan</strong></p>
        <p style="margin-top: 4px;">Compatible with InfinityFree &amp; Standard Shared Web Hosting</p>
      </footer>
    </main>
  </div>
</body>
</html>
