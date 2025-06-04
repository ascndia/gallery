<?php
// Include database connection
require_once '../includes/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get last image ID parameter (optional)
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Get limit parameter (optional)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

// Prepare query - get images newer than the last_id
$query = $db->prepare("SELECT * FROM images WHERE id > :lastId ORDER BY created_at DESC, id DESC LIMIT :limit");
$query->bindValue(':lastId', $lastId, SQLITE3_INTEGER);
$query->bindValue(':limit', $limit, SQLITE3_INTEGER);
$result = $query->execute();

// Prepare data for response
$images = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $images[] = $row;
}

// Get total count for info
$countQuery = $db->query("SELECT COUNT(*) as count FROM images");
$totalCount = $countQuery->fetchArray(SQLITE3_ASSOC)['count'];

// Return JSON response
echo json_encode([
    'images' => $images,
    'total_count' => $totalCount
]);
