<?php

/**
 * Password Migration Utility
 *
 * Rehashes plaintext passwords to bcrypt.
 * Run via CLI: php src/Utils/PasswordMigration.php
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

$dbPath = __DIR__ . '/../../storage/database.sqlite';

if (!file_exists($dbPath)) {
    fwrite(STDERR, "Database not found at: $dbPath\n");
    exit(1);
}

try {
    $pdo = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Password Migration Utility\n";
echo str_repeat('-', 40) . "\n";

// Find users with non-bcrypt passwords
$stmt = $pdo->query("SELECT id, username, password FROM users WHERE password NOT LIKE '\$2y\$%'");
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "All passwords are already bcrypt hashed. Nothing to do.\n";
    exit(0);
}

echo "Found " . count($users) . " user(s) with plaintext passwords.\n\n";

$updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$updated = 0;

foreach ($users as $user) {
    $hashed = password_hash($user['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $updateStmt->execute([$hashed, $user['id']]);
    $updated++;
    echo "[OK] User #{$user['id']} ({$user['username']}): password hashed with bcrypt (cost 12)\n";
}

echo "\n" . str_repeat('-', 40) . "\n";
echo "Done. Updated $updated user(s).\n";
