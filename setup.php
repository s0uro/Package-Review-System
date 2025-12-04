<?php
$db = new SQLite3('database.db');
$db->exec('CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT
)');

$db->exec('CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    package_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    status TEXT DEFAULT "pending",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES packages(id)
)');

$db->exec('CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    review_id INTEGER NOT NULL,
    moderator_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    action_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

$db->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT "user"
)');

// Insert sample data
$db->exec("INSERT OR IGNORE INTO packages (id, name, description) VALUES 
(1, 'PHP Package Manager', 'Advanced PHP package management system'),
(2, 'WordPress Plugin', 'Custom WordPress plugin for reviews')");

$db->exec("INSERT OR IGNORE INTO users (id, username, password_hash, role) VALUES 
(1, 'moderator', '" . password_hash('moderator123', PASSWORD_DEFAULT) . "', 'moderator'),
(2, 'user1', '" . password_hash('user123', PASSWORD_DEFAULT) . "', 'user')");

echo "Database setup complete! Visit index.php";
?>
