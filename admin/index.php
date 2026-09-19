<?php
require_once __DIR__ . '/auth.php';

$db = get_db();

// 1. Fetch statistics
$totalResponses = 0;
$avgPercentage = 0.0;
$highestScore = 0;
$lowestScore = 0;
$totalQuestions = 0;

try {
    // Total responses & scores
    $statStmt = $db->query("
        SELECT 
            COUNT(*) as total_count,
            AVG(percentage) as avg_percent,
            MAX(percentage) as max_percent,
            MIN(percentage) as min_percent
        FROM quiz_responses
    ");
    $stats = $statStmt->fetch();
    if ($stats && $stats['total_count'] > 0) {
        $totalResponses = (int)$stats['total_count'];
        $avgPercentage = round((float)$stats['avg_percent'], 1);
        $highestScore = round((float)$stats['max_percent'], 1);
        $lowestScore = round((float)$stats['min_percent'], 1);
    }

    // Total questions
    $qCountStmt = $db->query("SELECT COUNT(*) as count FROM questions WHERE is_active = 1");
    $totalQuestions = (int)($qCountStmt->fetch()['count'] ?? 0);

    // Recent 10 submissions
    $recentStmt = $db->query("
        SELECT id, friend_name, score, total_questions, percentage, submitted_at 
        FROM quiz_responses 
        ORDER BY submitted_at DESC, id DESC 
        LIMIT 10
    ");
    $recentResponses = $recentStmt->fetchAll();
} catch (Exception $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $recentResponses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Know Ali</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <!-- Sidebar Navigation -->
    <?php render_admin_nav('dashboard'); ?>

    <!-- Main Content -->
    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em;">
            Overview Dashboard
          </h1>
          <p class="text-muted" style="font-size: 0.95rem;">
            Real-time friendship quiz analytics for Ali Sultan
          </p>
        </div>
        <div class="flex-between gap-2">
          <a href="responses.php" class="btn btn-secondary btn-sm">
            <span>👥 View All Responses</span>
          </a>
          <a href="../index.php" target="_blank" class="btn btn-primary btn-sm">
            <span>🚀 Open Quiz</span>
          </a>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Total Quizzes Taken</div>
          <div class="stat-val"><?= $totalResponses ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Average Score</div>
          <div class="stat-val" style="color: var(--primary-light);">
            <?= $totalResponses > 0 ? "{$avgPercentage}%" : "—" ?>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Highest Score</div>
          <div class="stat-val" style="color: #34D399;">
            <?= $totalResponses > 0 ? "{$highestScore}%" : "—" ?>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Lowest Score</div>
          <div class="stat-val" style="color: #F87171;">
            <?= $totalResponses > 0 ? "{$lowestScore}%" : "—" ?>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-label">Active Questions</div>
          <div class="stat-val"><?= $totalQuestions ?></div>
        </div>
      </div>

      <!-- Recent Submissions Section -->
      <div class="quiz-card" style="padding: 24px; border-radius: var(--radius-lg);">
        <div class="flex-between mb-4">
          <h2 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF;">
            Recent Submissions
          </h2>
          <?php if (!empty($recentResponses)): ?>
            <a href="responses.php" style="color: var(--primary-light); text-decoration: none; font-size: 0.88rem; font-weight: 600;">
              View all <?= $totalResponses ?> →
            </a>
          <?php endif; ?>
        </div>

        <?php if (empty($recentResponses)): ?>
          <div class="text-center" style="padding: 48px 20px;">
            <div style="font-size: 2.8rem; margin-bottom: 12px;">📭</div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">
              No quiz responses yet
            </h3>
            <p class="text-muted" style="max-width: 380px; margin: 0 auto 20px; font-size: 0.9rem;">
              Send the quiz link to your friends on WhatsApp to see who knows you best!
            </p>
            <a href="../index.php" target="_blank" class="btn btn-primary btn-sm">
              Take the Quiz Now
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Friend</th>
                  <th>Score</th>
                  <th>Percentage</th>
                  <th>Submitted At</th>
                  <th style="text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentResponses as $resp): ?>
                  <?php 
                    $pct = (float)$resp['percentage'];
                    $badgeClass = $pct >= 80 ? 'badge-success' : ($pct >= 50 ? 'badge-primary' : 'badge-danger');
                  ?>
                  <tr>
                    <td>
                      <strong style="color: #FFFFFF;"><?= h($resp['friend_name']) ?></strong>
                    </td>
                    <td>
                      <?= (int)$resp['score'] ?> / <?= (int)$resp['total_questions'] ?>
                    </td>
                    <td>
                      <span class="badge <?= $badgeClass ?>">
                        <?= $pct ?>%
                      </span>
                    </td>
                    <td class="text-muted" style="font-size: 0.85rem;">
                      <?= date('M j, Y • g:i a', strtotime($resp['submitted_at'])) ?>
                    </td>
                    <td style="text-align: right;">
                      <a href="response-view.php?id=<?= (int)$resp['id'] ?>" class="btn btn-secondary btn-sm">
                        View Answers 🔍
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
