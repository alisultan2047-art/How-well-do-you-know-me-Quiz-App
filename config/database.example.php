<?php
/**
 * Database Configuration for InfinityFree / Shared Hosting
 *
 * Copy this file to "config/database.php" and enter your real MySQL
 * credentials provided in your InfinityFree control panel (VistaPanel).
 */

// Hostname: Usually something like sql123.infinityfree.com or sql123.epizy.com
define('DB_HOST', 'sqlxxx.infinityfree.com');

// Database Name: Usually format like if0_12345678_aliquiz
define('DB_NAME', 'if0_xxxx_aliquiz');

// Username: Usually format like if0_12345678
define('DB_USER', 'if0_xxxx');

// Password: Your InfinityFree vPanel account password
define('DB_PASSWORD', 'YOUR_INFINITYFREE_PASSWORD_HERE');

// Port: Standard MySQL port is 3306
define('DB_PORT', '3306');

// Database Driver: Strictly 'mysql' for InfinityFree / Shared hosting MySQL & MariaDB
define('DB_DRIVER', 'mysql');

// Application Branding Name
define('APP_NAME', 'How Well Do You Know Ali?');
define('OWNER_NAME', 'Ali Sultan');
define('SITE_URL', ''); // Leave empty for auto-detect or set to e.g. 'https://yourdomain.com'
