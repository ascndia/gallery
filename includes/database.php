<?php
// Database settings for SQLite
$dbPath = __DIR__ . '/../data/gallery.db';
$dbDir = dirname($dbPath);

// Create data directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}

// Create or open the SQLite database
try {
    $db = new SQLite3($dbPath);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Create table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        url TEXT NOT NULL,
        context TEXT,
        caption_short TEXT,
        caption_long TEXT,
        caption_tags TEXT,
        created_at TIMESTAMP DEFAULT (datetime('now', 'localtime'))
    )";
    
    $result = $db->exec($sql);
    if (!$result) {
        die("Error creating table: " . $db->lastErrorMsg());
    }
} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage());
}
?>
