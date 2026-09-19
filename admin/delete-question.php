<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf($csrf)) {
        $qId = (int)($_POST['question_id'] ?? 0);
        if ($qId > 0) {
            try {
                $db = get_db();
                $stmt = $db->prepare("DELETE FROM questions WHERE id = ?");
                $stmt->execute([$qId]);
            } catch (Exception $e) {
                error_log("Delete question error: " . $e->getMessage());
            }
        }
    }
}

redirect('questions.php');
