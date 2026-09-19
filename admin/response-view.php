<?php
require_once __DIR__ . '/auth.php';

$responseId = (int)($_GET['id'] ?? 0);
if ($responseId <= 0) {
    redirect('responses.php');
}

$db = get_db();

// 1. Fetch master response record
try {
    $stmt = $db->prepare("SELECT * FROM quiz_responses WHERE id = ?");
    $stmt->execute([$responseId]);
    $response = $stmt->fetch();
} catch (Exception $e) {
    die("Error retrieving response.");
}

if (!$response) {
    redirect('responses.php');
}

// 2. Fetch individual answers with question details
try {
    $ansStmt = $db->prepare("
        SELECT 
            qa.id as answer_id,
            qa.selected_answer,
            qa.is_correct,
            q.id as question_id,
            q.question_text,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.correct_answer
        FROM quiz_answers qa
        JOIN questions q ON qa.question_id = q.id
        WHERE qa.response_id = ?
        ORDER BY q.order_num ASC, q.id ASC
    ");
    $ansStmt->execute([$responseId]);
    $answers = $ansStmt->fetchAll();
} catch (Exception $e) {
    die("Error retrieving answer details.");
}

$friendName = $response['friend_name'];
$score = (int)$response['score'];
$total = (int)$response['total_questions'];
$pct = (float)$response['percentage'];
$submittedAt = $response['submitted_at'];

$badgeClass = $pct >= 80 ? 'badge-success' : ($pct >= 50 ? 'badge-primary' : 'badge-danger');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Response: <?= h($friendName) ?> | Know Ali Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <?php render_admin_nav('responses'); ?>

    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <a href="responses.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.88rem; font-weight: 500;">
            ← Back to All Responses
          </a>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em; margin-top: 4px;">
            Submission Details: <?= h($friendName) ?>
          </h1>
        </div>

        <form method="POST" action="responses.php" onsubmit="return confirm('Permanently delete this submission?');">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="response_id" value="<?= $responseId ?>">
          <button type="submit" class="btn btn-danger btn-sm">
            🗑️ Delete Response
          </button>
        </form>
      </div>

      <!-- Overview Score Card -->
      <div class="quiz-card" style="padding: 24px; border-radius: var(--radius-lg); margin-bottom: 28px;">
        <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between;">
          <div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Friend Name</div>
            <strong style="font-size: 1.4rem; color: #FFFFFF;"><?= h($friendName) ?></strong>
            <div style="font-size: 0.85rem; color: var(--text-dim); margin-top: 2px;">
              Submitted on <?= date('F j, Y • g:i a', strtotime($submittedAt)) ?>
            </div>
          </div>

          <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right;">
              <div style="font-size: 0.85rem; color: var(--text-muted);">Calculated Score</div>
              <strong style="font-size: 1.6rem; color: var(--primary-light);"><?= $score ?> / <?= $total ?></strong>
            </div>
            <div>
              <span class="badge <?= $badgeClass ?>" style="font-size: 1.1rem; padding: 6px 16px;">
                <?= $pct ?>%
              </span>
            </div>
          </div>
        </div>
      </div>

      <h2 style="font-size: 1.3rem; font-weight: 700; color: #FFFFFF; margin-bottom: 16px;">
        Question-by-Question Breakdown (<?= count($answers) ?> Questions)
      </h2>

      <!-- Individual Question Cards -->
      <?php foreach ($answers as $idx => $ans): ?>
        <?php 
          $isCorrect = (bool)$ans['is_correct'];
          $userPick = $ans['selected_answer'];
          $correctPick = $ans['correct_answer'];

          $optionTexts = [
            'A' => $ans['option_a'],
            'B' => $ans['option_b'],
            'C' => $ans['option_c'],
            'D' => $ans['option_d'],
          ];
        ?>
        <div class="review-question-card <?= $isCorrect ? 'correct' : 'incorrect' ?>">
          <div class="flex-between" style="margin-bottom: 10px;">
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">
              Question <?= $idx + 1 ?>
            </span>
            <span class="badge <?= $isCorrect ? 'badge-success' : 'badge-danger' ?>">
              <?= $isCorrect ? '✓ Correct (+1)' : '✗ Incorrect (0)' ?>
            </span>
          </div>

          <h3 style="font-size: 1.1rem; font-weight: 600; color: #FFFFFF; margin-bottom: 14px; line-height: 1.4;">
            <?= h($ans['question_text']) ?>
          </h3>

          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
              <?php 
                $isSelected = ($userPick === $opt);
                $isTarget = ($correctPick === $opt);

                $bgStyle = 'background: rgba(255, 255, 255, 0.02);';
                $borderStyle = 'border: 1px solid var(--border-subtle);';
                $labelExtra = '';

                if ($isTarget && $isSelected) {
                    $bgStyle = 'background: rgba(16, 185, 129, 0.15);';
                    $borderStyle = 'border: 1px solid rgba(16, 185, 129, 0.4); font-weight: 600;';
                    $labelExtra = '<span class="badge badge-success" style="margin-left: auto;">Friend Selected &amp; Correct ✓</span>';
                } elseif ($isSelected && !$isTarget) {
                    $bgStyle = 'background: rgba(244, 63, 94, 0.15);';
                    $borderStyle = 'border: 1px solid rgba(244, 63, 94, 0.4); font-weight: 600;';
                    $labelExtra = '<span class="badge badge-danger" style="margin-left: auto;">Friend Selected ✗</span>';
                } elseif ($isTarget) {
                    $bgStyle = 'background: rgba(59, 130, 246, 0.1);';
                    $borderStyle = 'border: 1px dashed rgba(59, 130, 246, 0.4);';
                    $labelExtra = '<span class="badge badge-primary" style="margin-left: auto;">Correct Answer ✓</span>';
                }
              ?>
              <div style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: var(--radius-sm); <?= $bgStyle ?> <?= $borderStyle ?>">
                <span style="width: 24px; height: 24px; border-radius: 50%; background: rgba(255,255,255,0.08); display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0;">
                  <?= $opt ?>
                </span>
                <span style="color: var(--text-main); font-size: 0.95rem;">
                  <?= h($optionTexts[$opt] ?? '') ?>
                </span>
                <?= $labelExtra ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </main>
  </div>
</body>
</html>
