<?php
require_once __DIR__ . '/auth.php';

$db = get_db();
$message = '';
$error = '';

// Handle quick toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $error = "Invalid or expired security token.";
    } else {
        $qId = (int)($_POST['question_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0) === 1 ? 0 : 1;
        try {
            $tStmt = $db->prepare("UPDATE questions SET is_active = ? WHERE id = ?");
            $tStmt->execute([$newStatus, $qId]);
            $message = "Question #{$qId} status updated.";
        } catch (Exception $e) {
            $error = "Failed to update question status.";
        }
    }
}

// Fetch all questions
try {
    $stmt = $db->query("SELECT * FROM questions ORDER BY order_num ASC, id ASC");
    $questions = $stmt->fetchAll();
} catch (Exception $e) {
    $questions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Questions | Know Ali Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <?php render_admin_nav('questions'); ?>

    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em;">
            Quiz Questions (<?= count($questions) ?>)
          </h1>
          <p class="text-muted" style="font-size: 0.95rem;">
            Add, edit, reorder or disable multiple-choice questions for Ali Sultan
          </p>
        </div>
        <a href="add-question.php" class="btn btn-primary btn-sm">
          <span>➕ Add New Question</span>
        </a>
      </div>

      <?php if ($message !== ''): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #6EE7B7; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
          ✓ <?= h($message) ?>
        </div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 0.9rem;">
          ⚠️ <?= h($error) ?>
        </div>
      <?php endif; ?>

      <?php if (empty($questions)): ?>
        <div class="quiz-card text-center" style="padding: 48px 20px;">
          <div style="font-size: 2.8rem; margin-bottom: 12px;">❓</div>
          <h3 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">
            No questions have been added yet.
          </h3>
          <p class="text-muted" style="max-width: 400px; margin: 0 auto 20px; font-size: 0.9rem;">
            Start building your quiz by creating questions with 4 answer choices and 1 correct answer.
          </p>
          <a href="add-question.php" class="btn btn-primary btn-sm">
            Create First Question
          </a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($questions as $idx => $q): ?>
            <?php 
              $isActive = (bool)$q['is_active'];
              $correct = $q['correct_answer'];
            ?>
            <div class="quiz-card" style="padding: 20px; border-radius: var(--radius-lg); opacity: <?= $isActive ? '1' : '0.65' ?>;">
              <div class="flex-between" style="margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span style="font-weight: 700; color: var(--primary-light); font-size: 0.9rem;">
                    #<?= $idx + 1 ?> (Order: <?= (int)$q['order_num'] ?>)
                  </span>
                  <span class="badge <?= $isActive ? 'badge-success' : 'badge-danger' ?>">
                    <?= $isActive ? 'Active' : 'Disabled' ?>
                  </span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                  <!-- Toggle Active Form -->
                  <form method="POST" action="questions.php" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                    <input type="hidden" name="status" value="<?= $isActive ? '1' : '0' ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" title="Toggle Active">
                      <?= $isActive ? 'Disable' : 'Enable' ?>
                    </button>
                  </form>

                  <!-- Edit -->
                  <a href="edit-question.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm">
                    ✏️ Edit
                  </a>

                  <!-- Delete -->
                  <form method="POST" action="delete-question.php" style="display: inline;" onsubmit="return confirm('Permanently delete this question?');">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete Question">
                      🗑️
                    </button>
                  </form>
                </div>
              </div>

              <!-- Question text -->
              <h3 style="font-size: 1.15rem; font-weight: 700; color: #FFFFFF; margin-bottom: 16px;">
                <?= h($q['question_text']) ?>
              </h3>

              <!-- 4 Options Grid -->
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $key => $optVal): ?>
                  <?php $isAns = ($correct === $key); ?>
                  <div style="padding: 10px 14px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px; background: <?= $isAns ? 'rgba(16, 185, 129, 0.12)' : 'rgba(255, 255, 255, 0.02)' ?>; border: 1px solid <?= $isAns ? 'rgba(16, 185, 129, 0.4)' : 'var(--border-subtle)' ?>;">
                    <span style="width: 22px; height: 22px; border-radius: 50%; background: <?= $isAns ? '#10B981' : 'rgba(255,255,255,0.08)' ?>; color: #FFFFFF; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;">
                      <?= $key ?>
                    </span>
                    <span style="font-size: 0.9rem; color: <?= $isAns ? '#FFFFFF' : 'var(--text-muted)' ?>; font-weight: <?= $isAns ? '600' : '400' ?>;">
                      <?= h($optVal) ?>
                    </span>
                    <?php if ($isAns): ?>
                      <span class="badge badge-success" style="margin-left: auto; font-size: 0.72rem;">Correct Answer ✓</span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
