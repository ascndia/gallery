<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
// Include database connection
require_once '../includes/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get POST data (support both form data and JSON input)
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (strpos($contentType, 'application/json') !== false) {
    // Get the JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else {
    // Get regular form data
    $data = $_POST;
}

// Check if URL is provided
if (empty($data['url'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Image URL is required']);
    exit;
}

// Extract data
$url = trim($data['url']);
$context = trim($data['context'] ?? '');
$caption_short = trim($data['caption_short'] ?? '');
$caption_long = trim($data['caption_long'] ?? '');
$caption_tags = trim($data['caption_tags'] ?? '');

// Insert the image
$query = $db->prepare("INSERT INTO images (url, context, caption_short, caption_long, caption_tags) 
                    VALUES (:url, :context, :caption_short, :caption_long, :caption_tags)");
$query->bindValue(':url', $url, SQLITE3_TEXT);
$query->bindValue(':context', $context, SQLITE3_TEXT);
$query->bindValue(':caption_short', $caption_short, SQLITE3_TEXT);
$query->bindValue(':caption_long', $caption_long, SQLITE3_TEXT);
$query->bindValue(':caption_tags', $caption_tags, SQLITE3_TEXT);

$result = $query->execute();

if ($result) {
    // Get the ID of the inserted image
    $newImageId = $db->lastInsertRowID();

    // Get the newly inserted image data
    $imageQuery = $db->prepare("SELECT * FROM images WHERE id = :id");
    $imageQuery->bindValue(':id', $newImageId, SQLITE3_INTEGER);
    $imageResult = $imageQuery->execute();
    $imageData = $imageResult->fetchArray(SQLITE3_ASSOC);

    // Try to send a WebSocket notification about the new image
    try {
        $client = new WebSocket\Client("ws://localhost:8080");
        $client->send(json_encode([
            'type' => 'new_image',
            'image' => $imageData
        ]));
        $client->close();
    } catch (Exception $e) {
        // If WebSocket fails, we can silently continue - the polling fallback will handle it
        error_log("WebSocket notification failed: " . $e->getMessage());
    }
    $imageId = $db->lastInsertRowID();

    // Get the newly inserted image
    $query = $db->prepare("SELECT * FROM images WHERE id = :id");
    $query->bindValue(':id', $imageId, SQLITE3_INTEGER);
    $result = $query->execute();
    $image = $result->fetchArray(SQLITE3_ASSOC);

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => 'Image added successfully',
        'image' => $image
    ]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'success' => false,
        'message' => 'Error adding image: ' . $db->lastErrorMsg()
    ]);
}
