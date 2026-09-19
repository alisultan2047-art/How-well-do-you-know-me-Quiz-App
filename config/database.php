<?php
/**
 * Database Connection & Global Utilities
 * Built for InfinityFree PHP 8+ / MySQL & MariaDB shared hosting
 * (with local development fallback for containerized environments)
 */

// Production error hiding: Never expose raw exceptions, paths, or SQL to visitors
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Start session if not started already
if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS including reverse proxy SSL termination (e.g. Cloudflare, InfinityFree SSL)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// -------------------------------------------------------------
// Database Settings
// For InfinityFree: Enter your MySQL credentials below
// -------------------------------------------------------------
defined('DB_HOST')     || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_NAME')     || define('DB_NAME', getenv('DB_NAME') ?: 'aliquiz');
defined('DB_USER')     || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASSWORD') || define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
defined('DB_PORT')     || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_DRIVER')   || define('DB_DRIVER', getenv('DB_DRIVER') ?: 'auto');

// Branding
defined('APP_NAME')    || define('APP_NAME', 'How Well Do You Know Ali?');
defined('OWNER_NAME')  || define('OWNER_NAME', 'Ali Sultan');
defined('SITE_URL')    || define('SITE_URL', '');

/**
 * Renders a secure, user-friendly error page if database fails
 */
function render_safe_db_error(): void {
    if (!headers_sent()) {
        http_response_code(500);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Database Service Unavailable | How Well Do You Know Ali?</title>
      <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0B0F19; color: #F8FAFC; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .error-card { max-width: 480px; width: 100%; background: #111827; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 32px 24px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        h1 { font-size: 1.35rem; margin: 12px 0 8px; color: #FFFFFF; font-weight: 700; }
        p { font-size: 0.95rem; color: #94A3B8; line-height: 1.6; margin: 0 0 20px; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563EB; color: #FFFFFF; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.95rem; transition: background 0.2s; }
        .btn:hover { background: #1D4ED8; }
      </style>
    </head>
    <body>
      <div class="error-card">
        <div style="font-size: 2.8rem; margin-bottom: 8px;">🔌</div>
        <h1>Unable to Connect to Database</h1>
        <p>The application could not establish a connection with the database. If you are the website owner, please verify that your MySQL database credentials in <code>config/database.php</code> match your InfinityFree hosting panel and that your database tables have been imported.</p>
        <a href="index.php" class="btn">Try Again 🔄</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Returns a singleton PDO instance with prepared statement security
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = DB_DRIVER;

    // If driver is set to mysql or auto with non-localhost settings, connect to MySQL
    if ($driver === 'mysql' || ($driver === 'auto' && DB_HOST !== 'localhost' && DB_PASSWORD !== '')) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Log securely without leaking credentials to the client
            error_log("Production MySQL connection failed: " . $e->getMessage());
            if ($driver === 'mysql') {
                render_safe_db_error();
            }
        }
    }

    // Development fallback (used inside AI Studio container when local MySQL is not running)
    $dbDir = dirname(__DIR__) . '/data';
    if (!is_dir($dbDir)) {
        @mkdir($dbDir, 0755, true);
    }
    $sqliteFile = $dbDir . '/quiz.db';
    $isNew = !file_exists($sqliteFile);

    try {
        $pdo = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        // If new database file, bootstrap tables and seed sample questions
        if ($isNew || filesize($sqliteFile) === 0) {
            init_sqlite_schema($pdo);
        }
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        render_safe_db_error();
    }

    return $pdo;
}

/**
 * Initializes SQLite schema and seeds sample questions & admin user
 */
function init_sqlite_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question_text TEXT NOT NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NOT NULL,
            option_d TEXT NOT NULL,
            correct_answer TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            order_num INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS quiz_responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            friend_name TEXT NOT NULL,
            score INTEGER NOT NULL,
            total_questions INTEGER NOT NULL,
            percentage REAL NOT NULL,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS quiz_answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            response_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            selected_answer TEXT NOT NULL,
            is_correct INTEGER NOT NULL,
            FOREIGN KEY (response_id) REFERENCES quiz_responses(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Check if questions exist
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM questions");
    $count = $stmt->fetch()['cnt'];

    if ($count == 0) {
        $sampleQuestions = [
            [
                "What is Ali's favorite color?",
                "Electric Blue", "Crimson Red", "Emerald Green", "Matte Black",
                "A", 1
            ],
            [
                "Which technology field is Ali most interested in?",
                "Artificial Intelligence & Machine Learning", "Game Development", "Blockchain & Crypto", "Hardware & Robotics",
                "A", 2
            ],
            [
                "What type of software projects does Ali like building most?",
                "Full-Stack Web Apps with Smart AI Features", "Command Line Utility Scripts", "Static Landing Pages", "Native Mobile Games",
                "A", 3
            ],
            [
                "What is one of Ali's top hobbies outside technology?",
                "Photography & Exploring New Places", "Competitive Swimming", "Playing Classical Violin", "Baking Gourmet Pastries",
                "A", 4
            ],
            [
                "Which AI technology does Ali enjoy researching & testing?",
                "Autonomous Agents & Large Language Models", "Old-School Expert Systems", "Symbolic Theorem Provers", "Genetic Algorithms",
                "A", 5
            ],
            [
                "What kind of apps has Ali worked on?",
                "Interactive Web Dashboards & Developer Tools", "Legacy Banking Mainframes", "Printer Firmware", "3D CAD Software",
                "A", 6
            ],
            [
                "What is Ali's favorite type of academic subject?",
                "Computer Science & Modern Systems Design", "Medieval European History", "Organic Chemistry", "Microeconomics",
                "A", 7
            ],
            [
                "What is one country or travel destination on Ali's bucket list?",
                "Japan (Tokyo & Kyoto)", "Iceland", "Switzerland", "Australia",
                "A", 8
            ],
            [
                "What is Ali's preferred learning style?",
                "Hands-on building, hacking & experimenting", "Reading 800-page textbooks without writing code", "Listening to podcasts only", "Memorizing lecture slides",
                "A", 9
            ],
            [
                "What is Ali currently focusing on or studying?",
                "Advanced Full-Stack Engineering & AI Systems", "Ancient Archaeology", "Veterinary Medicine", "Maritime Navigation",
                "A", 10
            ]
        ];

        $ins = $pdo->prepare("INSERT INTO questions (question_text, option_a, option_b, option_c, option_d, correct_answer, order_num) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleQuestions as $q) {
            $ins->execute($q);
        }
    }

    // Note: admin_users is intentionally left unseeded so the website owner
    // creates their own secure password via /admin/setup.php.
}

/**
 * XSS prevention helper
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generates or retrieves CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates CSRF token
 */
function verify_csrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safe redirect helper
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Safe JSON output helper
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Helper to get score message and badge
 */
function get_score_feedback(float $percentage): array {
    if ($percentage >= 90) {
        return [
            'badge' => '🏆 Best Friend Status',
            'title' => 'Wow! You really know Ali! 🏆',
            'message' => 'Legendary! You know practically everything about Ali Sultan. Either you guys talk every single day, or you have an uncanny superpower of paying attention to every detail!',
            'color' => '#10B981', // green
            'class' => 'score-exceptional'
        ];
    } elseif ($percentage >= 70) {
        return [
            'badge' => '🔥 Close Friend',
            'title' => 'Pretty impressive! You know me well! 🔥',
            'message' => 'Solid score! You know Ali really well and definitely share plenty of great conversations and memories together.',
            'color' => '#3B82F6', // electric blue
            'class' => 'score-great'
        ];
    } elseif ($percentage >= 50) {
        return [
            'badge' => '😄 Good Pal',
            'title' => 'Not bad… but you still have some homework to do 😄',
            'message' => 'A respectable effort! You know a good chunk about Ali, but there are still some fun facts and interests left to discover.',
            'color' => '#F59E0B', // amber
            'class' => 'score-average'
        ];
    } elseif ($percentage >= 30) {
        return [
            'badge' => '😂 Friendly Acquaintance',
            'title' => 'You know a little about me… time to investigate! 😂',
            'message' => 'You got a few things right, but some of those were probably wild guesses! Time to hang out and grab lunch with Ali.',
            'color' => '#EC4899', // pink
            'class' => 'score-low'
        ];
    } else {
        return [
            'badge' => '👀 Stranger Danger?',
            'title' => 'Okay… do we even know each other? 😂',
            'message' => 'Are we even friends yet? Did you just meet Ali five minutes ago on the internet? Message him right now to break the ice!',
            'color' => '#EF4444', // red
            'class' => 'score-minimal'
        ];
    }
}
