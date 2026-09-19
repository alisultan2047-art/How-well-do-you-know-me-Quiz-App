<?php
require_once __DIR__ . '/auth.php';

$db = get_db();
$message = '';
$error = '';

// Handle Delete response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $error = "Invalid or expired security token.";
    } else {
        $deleteId = (int)($_POST['response_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $delStmt = $db->prepare("DELETE FROM quiz_responses WHERE id = ?");
                $delStmt->execute([$deleteId]);
                $message = "Response #{$deleteId} successfully removed.";
            } catch (Exception $e) {
                $error = "Failed to delete response.";
            }
        }
    }
}

// Sorting and filtering
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT * FROM quiz_responses";
$params = [];

if ($search !== '') {
    $sql .= " WHERE friend_name LIKE ?";
    $params[] = "%{$search}%";
}

switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY submitted_at ASC, id ASC";
        break;
    case 'highest':
        $sql .= " ORDER BY percentage DESC, submitted_at DESC";
        break;
    case 'lowest':
        $sql .= " ORDER BY percentage ASC, submitted_at DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY submitted_at DESC, id DESC";
        break;
}

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $responses = $stmt->fetchAll();
} catch (Exception $e) {
    $responses = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Friend Responses | Know Ali Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="admin-layout">
    <?php render_admin_nav('responses'); ?>

    <main class="admin-main">
      <div class="flex-between mb-4">
        <div>
          <h1 style="font-size: 1.85rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em;">
            Friend Submissions
          </h1>
          <p class="text-muted" style="font-size: 0.95rem;">
            Review scores and individual answers submitted by friends
          </p>
        </div>
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

      <!-- Filter / Search Card -->
      <div class="quiz-card" style="padding: 20px; border-radius: var(--radius-lg); margin-bottom: 24px;">
        <form method="GET" action="responses.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
          <div style="flex: 1; min-width: 220px;">
            <input 
              type="text" 
              name="q" 
              id="responseSearchInput" 
              class="form-input" 
              placeholder="Search by friend's name..." 
              value="<?= h($search) ?>"
            >
          </div>

          <div style="min-width: 170px;">
            <select name="sort" class="form-select" onchange="this.form.submit()">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
              <option value="highest" <?= $sort === 'highest' ? 'selected' : '' ?>>Highest Score</option>
              <option value="lowest" <?= $sort === 'lowest' ? 'selected' : '' ?>>Lowest Score</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary btn-sm">
            Search
          </button>

          <?php if ($search !== '' || $sort !== 'newest'): ?>
            <a href="responses.php" class="btn btn-secondary btn-sm">
              Clear
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Table Section -->
      <div class="quiz-card" style="padding: 24px; border-radius: var(--radius-lg);">
        <?php if (empty($responses)): ?>
          <div class="text-center" style="padding: 48px 20px;">
            <div style="font-size: 2.8rem; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: #FFFFFF; margin-bottom: 6px;">
              <?= $search !== '' ? "No responses matching \"" . h($search) . "\"" : "No quiz responses yet." ?>
            </h3>
            <p class="text-muted" style="font-size: 0.9rem;">
              When friends complete your quiz, their detailed scorecards will appear here.
            </p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table" id="responsesTable">
              <thead>
                <tr>
                  <th>Friend</th>
                  <th>Score</th>
                  <th>Percentage</th>
                  <th>Submitted At</th>
                  <th style="text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($responses as $resp): ?>
                  <?php 
                    $pct = (float)$resp['percentage'];
                    $badgeClass = $pct >= 80 ? 'badge-success' : ($pct >= 50 ? 'badge-primary' : 'badge-danger');
                  ?>
                  <tr>
                    <td class="friend-name-cell">
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
                    <td style="text-align: right; white-space: nowrap;">
                      <a href="response-view.php?id=<?= (int)$resp['id'] ?>" class="btn btn-secondary btn-sm" style="margin-right: 6px;">
                        View Answers 🔍
                      </a>
                      <form method="POST" action="responses.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this response?');">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="response_id" value="<?= (int)$resp['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Response">
                          🗑️
                        </button>
                      </form>
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

  <script src="../assets/js/app.js"></script>
</body>
</html>
