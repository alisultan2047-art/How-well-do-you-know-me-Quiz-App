<?php
require_once __DIR__ . '/auth.php';

$qId = (int)($_GET['id'] ?? 0);
if ($qId <= 0) {
    redirect('questions.php');
}

$db = get_db();
$error = '';

// Fetch existing question
try {
    $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([$qId]);
    $question = $stmt->fetch();
} catch (Exception $e) {
    die("Error retrieving question.");
}

if (!$question) {
    redirect('questions.php');
}

// Process Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $error = "Security validation failed. Please refresh and try again.";
    } else {
        $qText = trim($_POST['question_text'] ?? '');
        $optA = trim($_POST['option_a'] ?? '');
        $optB = trim($_POST['option_b'] ?? '');
        $optC = trim($_POST['option_c'] ?? '');
        $optD = trim($_POST['option_d'] ?? '');
        $correct = strtoupper(trim($_POST['correct_answer'] ?? 'A'));
        $orderNum = (int)($_POST['order_num'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($qText === '' || $optA === '' || $optB === '' || $optC === '' || $optD === '') {
            $error = "Please fill in the question text and all four options.";
        } elseif (!in_array($correct, ['A', 'B', 'C', 'D'], true)) {
            $error = "Please select a valid correct answer (A, B, C, or D).";
        } else {
            try {
                $upStmt = $db->prepare("
                    UPDATE questions 
                    SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_answer = ?, order_num = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $upStmt->execute([$qText, $optA, $optB, $optC, $optD, $correct, $orderNum, $isActive, $qId]);
                redirect('questions.php');
            } catch (Exception $e) {
                error_log("Update question error: " . $e->getMessage());
                $error = "Failed to update question in the database.";
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
  <title>Edit Question #<?= $qId ?> | Know Ali Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <?php render_admin_nav('questions'); ?>

    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <a href="questions.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.88rem; font-weight: 500;">
            ← Back to Questions
          </a>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em; margin-top: 4px;">
            Edit Question #<?= $qId ?>
          </h1>
        </div>
      </div>

      <?php if ($error !== ''): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
          ⚠️ <?= h($error) ?>
        </div>
      <?php endif; ?>

      <div class="quiz-card" style="max-width: 720px; padding: 28px; border-radius: var(--radius-lg);">
        <form action="edit-question.php?id=<?= $qId ?>" method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="form-group">
            <label for="question_text" class="form-label">
              Question Text <span style="color: var(--primary-light);">*</span>
            </label>
            <textarea 
              name="question_text" 
              id="question_text" 
              rows="3" 
              class="form-textarea" 
              required
            ><?= h($_POST['question_text'] ?? $question['question_text']) ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label for="option_a" class="form-label">Option A <span style="color: var(--primary-light);">*</span></label>
              <input type="text" name="option_a" id="option_a" class="form-input" value="<?= h($_POST['option_a'] ?? $question['option_a']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label for="option_b" class="form-label">Option B <span style="color: var(--primary-light);">*</span></label>
              <input type="text" name="option_b" id="option_b" class="form-input" value="<?= h($_POST['option_b'] ?? $question['option_b']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label for="option_c" class="form-label">Option C <span style="color: var(--primary-light);">*</span></label>
              <input type="text" name="option_c" id="option_c" class="form-input" value="<?= h($_POST['option_c'] ?? $question['option_c']) ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label for="option_d" class="form-label">Option D <span style="color: var(--primary-light);">*</span></label>
              <input type="text" name="option_d" id="option_d" class="form-input" value="<?= h($_POST['option_d'] ?? $question['option_d']) ?>" required>
            </div>
          </div>

          <?php 
            $curCorrect = $_POST['correct_answer'] ?? $question['correct_answer'];
            $curOrder = $_POST['order_num'] ?? $question['order_num'];
            $curActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : (bool)$question['is_active'];
          ?>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="form-group" style="margin-bottom: 0;">
              <label for="correct_answer" class="form-label">Correct Answer</label>
              <select name="correct_answer" id="correct_answer" class="form-select">
                <option value="A" <?= $curCorrect === 'A' ? 'selected' : '' ?>>Option A</option>
                <option value="B" <?= $curCorrect === 'B' ? 'selected' : '' ?>>Option B</option>
                <option value="C" <?= $curCorrect === 'C' ? 'selected' : '' ?>>Option C</option>
                <option value="D" <?= $curCorrect === 'D' ? 'selected' : '' ?>>Option D</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label for="order_num" class="form-label">Display Order</label>
              <input type="number" name="order_num" id="order_num" class="form-input" value="<?= (int)$curOrder ?>" min="0">
            </div>
          </div>

          <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 28px;">
            <input type="checkbox" name="is_active" id="is_active" value="1" <?= $curActive ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
            <label for="is_active" style="color: var(--text-main); font-weight: 500; cursor: pointer;">
              Active in quiz (public users will see this question)
            </label>
          </div>

          <div style="display: flex; gap: 12px;">
            <a href="questions.php" class="btn btn-secondary" style="flex: 1;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="flex: 2;">Update Question</button>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
