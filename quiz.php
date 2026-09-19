<?php
require_once __DIR__ . '/config/database.php';

$friendName = trim($_GET['friend_name'] ?? $_SESSION['friend_name'] ?? '');
$nickname = trim($_GET['nickname'] ?? $_SESSION['nickname'] ?? '');

if ($friendName === '') {
    redirect('index.php?error=missing_name');
}

// Length restrictions
if (mb_strlen($friendName, 'UTF-8') > 60) {
    $friendName = mb_substr($friendName, 0, 60, 'UTF-8');
}
if (mb_strlen($nickname, 'UTF-8') > 40) {
    $nickname = mb_substr($nickname, 0, 40, 'UTF-8');
}

// Save to session for persistence
$_SESSION['friend_name'] = $friendName;
$_SESSION['nickname'] = $nickname;

$displayName = $nickname !== '' ? "{$friendName} ({$nickname})" : $friendName;

// Fetch questions from database without exposing correct answers
try {
    $db = get_db();
    // Exclude 'correct_answer' for absolute security
    $stmt = $db->query("
        SELECT id, question_text, option_a, option_b, option_c, option_d 
        FROM questions 
        WHERE is_active = 1 
        ORDER BY order_num ASC, id ASC
    ");
    $questions = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error loading quiz questions. Please try again later.");
}

if (empty($questions)) {
    redirect('index.php?error=no_questions');
}

$totalQuestions = count($questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz: How Well Do You Know Ali? | <?= h($displayName) ?></title>
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
        <div class="quiz-badge">
          <span>👤</span>
          <span><?= h($displayName) ?></span>
        </div>
      </div>
    </header>

    <!-- Main Container -->
    <main class="container">
      <!-- Progress Bar & Top Meta -->
      <div class="quiz-meta-bar">
        <span>Question <strong id="currentQuestionNumber">1</strong> of <?= $totalQuestions ?></span>
        <span class="text-muted">Target: Ali Sultan</span>
      </div>

      <div class="progress-track">
        <div class="progress-fill" id="progressFill" style="width: <?= round((1 / $totalQuestions) * 100) ?>%;"></div>
      </div>

      <!-- Quiz Form -->
      <form id="quizForm" action="submit.php" method="POST">
        <!-- Anti-CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="friend_name" value="<?= h($friendName) ?>">
        <input type="hidden" name="nickname" value="<?= h($nickname) ?>">

        <div class="quiz-card">
          <!-- Validation Warning -->
          <div id="validationHint" style="display: none; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 0.9rem; text-align: center;">
            ⚠️ Please choose an answer to proceed to the next question!
          </div>

          <!-- Questions container -->
          <div class="question-container">
            <?php foreach ($questions as $index => $q): ?>
              <div 
                class="question-step" 
                data-question-id="<?= (int)$q['id'] ?>" 
                style="<?= $index === 0 ? '' : 'display: none;' ?>"
              >
                <h2 class="question-text">
                  <?= h($q['question_text']) ?>
                </h2>

                <div class="options-list">
                  <button type="button" class="option-btn" data-option="A">
                    <span class="option-key">A</span>
                    <span class="option-text"><?= h($q['option_a']) ?></span>
                    <span class="option-indicator"></span>
                  </button>

                  <button type="button" class="option-btn" data-option="B">
                    <span class="option-key">B</span>
                    <span class="option-text"><?= h($q['option_b']) ?></span>
                    <span class="option-indicator"></span>
                  </button>

                  <button type="button" class="option-btn" data-option="C">
                    <span class="option-key">C</span>
                    <span class="option-text"><?= h($q['option_c']) ?></span>
                    <span class="option-indicator"></span>
                  </button>

                  <button type="button" class="option-btn" data-option="D">
                    <span class="option-key">D</span>
                    <span class="option-text"><?= h($q['option_d']) ?></span>
                    <span class="option-indicator"></span>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Quiz Navigation -->
          <div class="quiz-nav">
            <button type="button" id="prevQuestionBtn" class="btn btn-secondary" disabled>
              ← Previous
            </button>

            <button type="button" id="nextQuestionBtn" class="btn btn-primary">
              Next Question →
            </button>

            <button type="button" id="submitQuizBtn" class="btn btn-success" style="display: none;">
              Submit Quiz ✨
            </button>
          </div>
        </div>
      </form>
    </main>
  </div>

  <!-- Submission Confirmation Modal -->
  <div class="modal-backdrop" id="submitModal">
    <div class="modal-content text-center">
      <div style="font-size: 3rem; margin-bottom: 12px;">🎯</div>
      <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 8px; color: #FFFFFF;">
        Submit Your Answers?
      </h3>
      <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 24px;">
        You've reached the end! Ready to see how well you really know Ali Sultan?
      </p>

      <div style="display: flex; gap: 12px; justify-content: center;">
        <button type="button" id="cancelSubmitBtn" class="btn btn-secondary" style="flex: 1;">
          Review
        </button>
        <button type="button" id="confirmSubmitBtn" class="btn btn-primary" style="flex: 1;">
          Yes, Submit! 🚀
        </button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
