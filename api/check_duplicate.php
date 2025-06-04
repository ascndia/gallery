<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['url']) || empty(trim($input['url']))) {
        http_response_code(400);
        echo json_encode(['error' => 'URL is required']);
        exit();
    }

    $url = trim($input['url']);

    // Database connection
    $db = new PDO('sqlite:../data/gallery.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if URL already exists
    $stmt = $db->prepare("SELECT id, caption_short, created_at FROM images WHERE url = ? LIMIT 1");
    $stmt->execute([$url]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // URL exists
        echo json_encode([
            'exists' => true,
            'image' => [
                'id' => $existing['id'],
                'caption_short' => $existing['caption_short'],
                'created_at' => $existing['created_at']
            ]
        ]);
    } else {
        // URL doesn't exist
        echo json_encode(['exists' => false]);
    }
} catch (PDOException $e) {
    error_log("Database error in check_duplicate.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Error in check_duplicate.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
