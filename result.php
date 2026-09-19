<?php
require_once __DIR__ . '/config/database.php';

$responseId = (int)($_GET['id'] ?? 0);

if ($responseId <= 0) {
    redirect('index.php');
}

// Privacy Protection: Prevent visitors from guessing or enumerating other friends' results
$isAdmin = !empty($_SESSION['admin_user_id']);
$authorizedIds = $_SESSION['authorized_quiz_ids'] ?? [];
if (!is_array($authorizedIds)) {
    $authorizedIds = [];
}
$isAuthorized = $isAdmin || in_array($responseId, $authorizedIds, true);

if (!$isAuthorized) {
    // Unauthorized access to another person's quiz score: redirect home
    redirect('index.php');
}

$db = get_db();
try {
    $stmt = $db->prepare("SELECT * FROM quiz_responses WHERE id = ?");
    $stmt->execute([$responseId]);
    $response = $stmt->fetch();
} catch (Exception $e) {
    die("Something went wrong loading your quiz score. Please try again later.");
}

if (!$response) {
    redirect('index.php');
}

$friendName = $response['friend_name'];
$score = (int)$response['score'];
$total = (int)$response['total_questions'];
$percentage = (float)$response['percentage'];

// Determine feedback message tier
$feedback = get_score_feedback($percentage);

// Conic angle for circle animation
$conicAngle = round(($percentage / 100) * 360);

// Base quiz share URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$quizShareUrl = $protocol . $host . $baseDir . '/index.php';

// WhatsApp share text
$whatsAppMsg = "Hey! I took Ali Sultan's quiz and scored {$score}/{$total} ({$percentage}%)! Think you can beat me? 😂 Try it here: " . $quizShareUrl;
$whatsAppUrl = "https://api.whatsapp.com/send?text=" . urlencode($whatsAppMsg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Score: <?= $score ?>/<?= $total ?> | How Well Do You Know Ali?</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    :root {
      --score-color: <?= $feedback['color'] ?>;
      --score-angle: <?= $conicAngle ?>deg;
      --score-glow: <?= $feedback['color'] ?>40;
    }
  </style>
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
        <a href="index.php" class="header-link">New Quiz 🔄</a>
      </div>
    </header>

    <!-- Main Container -->
    <main class="container result-view-container">
      <div class="quiz-card text-center">
        <!-- Result Tag -->
        <div class="result-badge" style="color: <?= $feedback['color'] ?>; border-color: <?= $feedback['color'] ?>40;">
          <?= $feedback['badge'] ?>
        </div>

        <h1 style="font-size: 1.8rem; font-weight: 800; color: #FFFFFF; margin-bottom: 6px;">
          <?= h($friendName) ?>'s Score
        </h1>
        <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 24px;">
          Ali knows you know him! 😎
        </p>

        <!-- Visual Animated Score Circle -->
        <div class="score-circle-wrapper">
          <div class="score-circle">
            <span class="score-number" id="animatedScoreVal" data-score="<?= $score ?>"><?= $score ?></span>
            <span class="score-total">out of <?= $total ?></span>
          </div>
        </div>

        <div class="score-percentage">
          <?= $percentage ?>% Correct
        </div>

        <!-- Custom Result Tier Feedback -->
        <div class="result-card-message">
          <h2 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF; margin-bottom: 10px;">
            <?= $feedback['title'] ?>
          </h2>
          <p><?= $feedback['message'] ?></p>
        </div>

        <!-- Share Actions -->
        <div class="action-grid">
          <!-- WhatsApp Share -->
          <a href="<?= $whatsAppUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-block whatsapp-btn">
            <span style="font-size: 1.2rem;">💬</span>
            <span>Share on WhatsApp</span>
          </a>

          <!-- Copy Link -->
          <button type="button" id="copyQuizLinkBtn" class="btn btn-block btn-secondary" data-url="<?= h($quizShareUrl) ?>">
            <span>📋</span>
            <span>Copy Quiz Link</span>
          </button>

          <!-- Challenge Another Friend -->
          <a href="index.php" class="btn btn-block btn-primary">
            <span>🚀</span>
            <span>Challenge your friends</span>
          </a>
        </div>
      </div>
    </main>
  </div>

  <!-- Toast Notification -->
  <div class="toast-msg" id="linkToast">
    Link copied! 📋
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
