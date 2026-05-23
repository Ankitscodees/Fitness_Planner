<?php
// ============================================================
// includes/db.php — PDO Database Connection
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'fitpro');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#1a1a1a;color:#ff4d4d;padding:30px;margin:20px;border-radius:8px;border:1px solid #ff4d4d;">
                <strong>❌ Database Connection Failed</strong><br><br>
                ' . htmlspecialchars($e->getMessage()) . '<br><br>
                <small>Please check your database credentials in <code>includes/db.php</code> and ensure MySQL is running.</small>
                </div>');
        }
    }
    return $pdo;
}
