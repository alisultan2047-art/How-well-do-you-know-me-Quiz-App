<?php
require_once __DIR__ . '/auth.php';

$zipPath = dirname(__DIR__) . '/InfinityFree_Ali_Quiz_Deploy.zip';

// If pre-built zip doesn't exist, generate it on the fly if ZipArchive is enabled
if (!file_exists($zipPath) && class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $root = dirname(__DIR__);
        $filesToInclude = [
            'index.php',
            'quiz.php',
            'submit.php',
            'result.php',
            'database.sql',
            'README.md',
            '.htaccess',
            'config/database.example.php',
            'config/.htaccess',
            'assets/css/style.css',
            'assets/js/app.js',
            'admin/auth.php',
            'admin/index.php',
            'admin/login.php',
            'admin/logout.php',
            'admin/setup.php',
            'admin/questions.php',
            'admin/add-question.php',
            'admin/edit-question.php',
            'admin/delete-question.php',
            'admin/responses.php',
            'admin/response-view.php',
            'admin/settings.php',
            'admin/download-zip.php',
        ];

        foreach ($filesToInclude as $file) {
            $abs = $root . '/' . $file;
            if (file_exists($abs)) {
                $zip->addFile($abs, $file);
            }
        }
        $zip->close();
    }
}

if (!file_exists($zipPath)) {
    die("Deployment ZIP file is currently generating or unavailable. Please run generation script or check back shortly.");
}

$fileName = 'InfinityFree_Ali_Quiz_Deploy.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($zipPath);
exit;
