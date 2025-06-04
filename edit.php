<?php
// Include database connection
require_once 'includes/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Get image details for editing
$query = $db->prepare("SELECT * FROM images WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $query->execute();
$image = $result->fetchArray(SQLITE3_ASSOC);

if (!$image) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Process form submission for updating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');
    $context = trim($_POST['context'] ?? '');
    $caption_short = trim($_POST['caption_short'] ?? '');
    $caption_long = trim($_POST['caption_long'] ?? '');
    $caption_tags = trim($_POST['caption_tags'] ?? '');

    // Validate URL (basic validation)
    if (empty($url)) {
        $message = 'URL is required';
        $messageType = 'danger';
    } else {
        // Update the image
        $query = $db->prepare("UPDATE images SET 
                            url = :url, 
                            context = :context, 
                            caption_short = :caption_short, 
                            caption_long = :caption_long, 
                            caption_tags = :caption_tags 
                            WHERE id = :id");

        $query->bindValue(':url', $url, SQLITE3_TEXT);
        $query->bindValue(':context', $context, SQLITE3_TEXT);
        $query->bindValue(':caption_short', $caption_short, SQLITE3_TEXT);
        $query->bindValue(':caption_long', $caption_long, SQLITE3_TEXT);
        $query->bindValue(':caption_tags', $caption_tags, SQLITE3_TEXT);
        $query->bindValue(':id', $id, SQLITE3_INTEGER);

        $result = $query->execute();

        if ($result) {
            $message = 'Image updated successfully';
            $messageType = 'success';

            // Refresh image data
            $query = $db->prepare("SELECT * FROM images WHERE id = :id");
            $query->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $query->execute();
            $image = $result->fetchArray(SQLITE3_ASSOC);
        } else {
            $message = 'Error updating image: ' . $db->lastErrorMsg();
            $messageType = 'danger';
        }
    }
}

// Process delete request
if (isset($_POST['delete']) && $_POST['delete'] == 1) {
    $query = $db->prepare("DELETE FROM images WHERE id = :id");
    $query->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $query->execute();

    if ($result) {
        header('Location: index.php');
        exit;
    } else {
        $message = 'Error deleting image: ' . $db->lastErrorMsg();
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Gallery</a></li>
                <li class="breadcrumb-item"><a href="view.php?id=<?php echo $id; ?>">Image Details</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Image</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Image</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-4">

                        <img class="img-fluid rounded" src="proxy.php?url=<?= urlencode($image['url']) ?>"
                            data-original="<?= htmlspecialchars($image['url']) ?>"
                            onerror="retryWithProxy(this)">
                    </div>
                    <div class="col-md-8">
                        <form method="post">
                            <div class="mb-3">
                                <label for="url" class="form-label">Image URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($image['url']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="context" class="form-label">Context</label>
                                <textarea class="form-control" id="context" name="context" rows="2"><?php echo htmlspecialchars($image['context']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="caption_short" class="form-label">Short Caption</label>
                                <input type="text" class="form-control" id="caption_short" name="caption_short" value="<?php echo htmlspecialchars($image['caption_short']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="caption_long" class="form-label">Long Caption</label>
                                <textarea class="form-control" id="caption_long" name="caption_long" rows="3"><?php echo htmlspecialchars($image['caption_long']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="caption_tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="caption_tags" name="caption_tags" value="<?php echo htmlspecialchars($image['caption_tags']); ?>">
                                <div class="form-text">Comma-separated tags</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Image</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this image? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" name="delete" value="1">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>