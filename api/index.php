<?php
// Include database connection
require_once '../includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get total images count
$query = $db->query("SELECT COUNT(*) as count FROM images");
$totalCount = $query->fetchArray(SQLITE3_ASSOC)['count'];

echo json_encode([
    'success' => true,
    'message' => 'Gallery API is running',
    'totalImages' => $totalCount,
    'endpoints' => [
        'add' => '/api/add.php - POST method to add new images',
        'example' => [
            'POST /api/add.php',
            'Content-Type: application/json',
            'Body: { "url": "https://example.com/image.jpg", "context": "Test image", "caption_short": "Test", "caption_tags": "test,api" }'
        ]
    ]
]);
?>
