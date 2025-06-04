<?php
// Include database connection
require_once 'includes/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Get image details
$query = $db->prepare("SELECT * FROM images WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $query->execute();
$image = $result->fetchArray(SQLITE3_ASSOC);

if (!$image) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Details - <?php echo htmlspecialchars($image['caption_short']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Gallery</a></li>
                <li class="breadcrumb-item active" aria-current="page">Image Details</li>
            </ol>
        </nav>

        <div class="card mb-4">
            <div class="row g-0">
                <div class="col-md-6">
                    <img class="img-fluid" src="proxy.php?url=<?= urlencode($image['url']) ?>"
                        data-original="<?= htmlspecialchars($image['url']) ?>"
                        onerror="retryWithProxy(this)">
                </div>
                <div class="col-md-6">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($image['caption_short']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">Context</h6>
                        <p class="card-text"><?php echo htmlspecialchars($image['context']); ?></p>

                        <h6 class="card-subtitle mb-2 text-muted">Full Caption</h6>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($image['caption_long'])); ?></p>

                        <h6 class="card-subtitle mb-2 text-muted">Tags</h6>
                        <p>
                            <?php
                            $tags = explode(',', $image['caption_tags']);
                            foreach ($tags as $tag) {
                                $tag = trim($tag);
                                if (!empty($tag)) {
                                    echo '<span class="badge bg-secondary me-1">' . htmlspecialchars($tag) . '</span>';
                                }
                            }
                            ?>
                        </p>

                        <div class="mt-3">
                            <a href="edit.php?id=<?php echo $image['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="index.php" class="btn btn-secondary">Back to Gallery</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                <small>Image URL: <a href="<?php echo htmlspecialchars($image['url']); ?>" target="_blank"><?php echo htmlspecialchars($image['url']); ?></a></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>