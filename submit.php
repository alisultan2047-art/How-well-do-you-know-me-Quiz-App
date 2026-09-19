<?php
require_once __DIR__ . '/config/database.php';

// Ensure method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// Validate CSRF token
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf($csrf)) {
    die("Invalid or expired session token. Please reload and try again.");
}

// Friend name validation & input length limits
$friendName = trim($_POST['friend_name'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$submittedAnswers = $_POST['answers'] ?? [];

if ($friendName === '') {
    redirect('index.php?error=missing_name');
}

// Cap max length to prevent database field overflow or abuse
if (mb_strlen($friendName, 'UTF-8') > 60) {
    $friendName = mb_substr($friendName, 0, 60, 'UTF-8');
}
if (mb_strlen($nickname, 'UTF-8') > 40) {
    $nickname = mb_substr($nickname, 0, 40, 'UTF-8');
}

$storedName = $nickname !== '' ? "{$friendName} ({$nickname})" : $friendName;

$db = get_db();

try {
    // Retrieve all active questions with their TRUE correct answers from database
    $stmt = $db->query("
        SELECT id, correct_answer 
        FROM questions 
        WHERE is_active = 1 
        ORDER BY order_num ASC, id ASC
    ");
    $questions = $stmt->fetchAll();

    if (empty($questions)) {
        die("No questions found in the system to score against.");
    }

    $totalQuestions = count($questions);
    $score = 0;
    $processedAnswers = [];

    // Compare each submitted answer with correct answer in MySQL
    foreach ($questions as $q) {
        $qId = (int)$q['id'];
        $correctAnswer = strtoupper(trim($q['correct_answer']));
        $selectedAnswer = isset($submittedAnswers[$qId]) ? strtoupper(trim($submittedAnswers[$qId])) : '';

        // Validate choice is one of A, B, C, D
        if (!in_array($selectedAnswer, ['A', 'B', 'C', 'D'], true)) {
            $selectedAnswer = 'N/A';
        }

        $isCorrect = ($selectedAnswer === $correctAnswer) ? 1 : 0;
        if ($isCorrect) {
            $score++;
        }

        $processedAnswers[] = [
            'question_id' => $qId,
            'selected_answer' => $selectedAnswer,
            'is_correct' => $isCorrect
        ];
    }

    $percentage = round(($score / $totalQuestions) * 100, 1);

    // Save transactionally to MySQL
    $db->beginTransaction();

    // 1. Insert overall quiz response
    $insertResp = $db->prepare("
        INSERT INTO quiz_responses (friend_name, score, total_questions, percentage, submitted_at) 
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $insertResp->execute([$storedName, $score, $totalQuestions, $percentage]);
    $responseId = (int)$db->lastInsertId();

    // 2. Insert individual question audit records
    $insertAns = $db->prepare("
        INSERT INTO quiz_answers (response_id, question_id, selected_answer, is_correct) 
        VALUES (?, ?, ?, ?)
    ");
    foreach ($processedAnswers as $ans) {
        $insertAns->execute([
            $responseId,
            $ans['question_id'],
            $ans['selected_answer'],
            $ans['is_correct']
        ]);
    }

    $db->commit();

    // Store in session for immediate private view authorization (protects public privacy)
    if (!isset($_SESSION['authorized_quiz_ids']) || !is_array($_SESSION['authorized_quiz_ids'])) {
        $_SESSION['authorized_quiz_ids'] = [];
    }
    $_SESSION['authorized_quiz_ids'][] = $responseId;
    $_SESSION['latest_response_id'] = $responseId;

    // Redirect to result page
    redirect("result.php?id={$responseId}");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Submission error: " . $e->getMessage());
    die("We couldn't save your answers. Please try again later.");
}
