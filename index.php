<?php
// Include database connection
require_once 'includes/database.php';

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Masonry columns setting
$cols = isset($_GET['cols']) ? (int)$_GET['cols'] : 4;
if ($cols < 1) $cols = 1;
if ($cols > 12) $cols = 12;

// Calculate column classes based on cols parameter
$colClassMd = floor(12 / min(3, $cols)); // Max 3 columns on medium devices
$colClassLg = floor(12 / $cols);         // Full column count on large devices

$offset = ($page - 1) * $limit;

// Get total number of images
$countQuery = $db->query("SELECT COUNT(*) as count FROM images");
$totalCount = $countQuery->fetchArray(SQLITE3_ASSOC)['count'];
$totalPages = ceil($totalCount / $limit);

// Get images for current page
$query = $db->prepare("SELECT * FROM images ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$query->bindValue(':limit', $limit, SQLITE3_INTEGER);
$query->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $query->execute();

// Prepare data for display
$images = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $images[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet"><!-- Lightbox2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/css/lightbox.min.css" rel="stylesheet">
    <!-- Masonry layout -->
    <script src="https://cdn.jsdelivr.net/npm/masonry-layout@4.2.2/dist/masonry.pkgd.min.js" integrity="sha384-GNFwBvfVxBkLMJpYMOABq3c+d3KnQxudP/mGPkzpZSTYykLBNsZEnG2D9G/X/+7D" crossorigin="anonymous"></script>
    <!-- ImagesLoaded library for Masonry -->
    <script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>
    <style>
        .masonry-grid {
            margin: 0 -10px;
        }

        .masonry-item {
            padding-left: 10px;
            padding-right: 10px;
        }

        .masonry-content {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .masonry-img {
            width: 100%;
            display: block;
            transition: all 0.3s ease;
        }

        /* Image placeholder and loading styles */
        .image-container {
            position: relative;
            overflow: hidden;
            min-height: 150px;
            background: linear-gradient(45deg, #f3f3f3, #e9e9e9);
        }

        .image-container img {
            width: 100%;
            height: auto;
            display: block;
            transition: opacity 0.5s ease;
        }

        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }

        .image-actions .dropdown-menu {
            width: 300px;
            padding: 15px;
        }

        .image-actions .btn {
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
        }

        .image-tag {
            display: inline-block;
            font-size: 0.75rem;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .image-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .masonry-content:hover .image-caption {
            opacity: 1;
        }

        /* Lightbox customization */
        .masonry-content a {
            cursor: zoom-in;
            display: block;
        }

        /* Override lightbox styles for better appearance */
        .lb-data .lb-caption {
            font-size: 16px;
            font-weight: normal;
        }

        .lb-data .lb-details {
            width: 90%;
            margin-bottom: 10px;
        }

        .lb-closeContainer {
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="my-3 d-flex justify-content-between px-2">
            <div class="d-flex gap-2 align-items-center">
                <button id="checkUrlBtn" class="btn btn-primary">Check URL</button>
                <button id="quickAddBtn" class="btn btn-success">Quick Add</button>
                <span class="badge bg-secondary"><?php echo $totalCount; ?> images in gallery</span>
            </div>
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Gallery pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&cols=<?php echo $cols; ?>" tabindex="-1">Previous</a>
                        </li>

                        <?php
                        // Show limited page numbers with ellipsis
                        $start = max(1, $page - 2);
                        $end = min($start + 4, $totalPages);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&limit=' . $limit . '&cols=' . $cols . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                <a class="page-link" href="?page=' . $i . '&limit=' . $limit . '&cols=' . $cols . '">' . $i . '</a>
                              </li>';
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&limit=' . $limit . '&cols=' . $cols . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&cols=<?php echo $cols; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            <div>
                <form class="d-flex" method="get">
                    <div class="form-group me-2">
                        <label for="limitSelect" class="me-2">Images per page:</label>
                        <select id="limitSelect" name="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="30" <?php echo $limit == 30 ? 'selected' : ''; ?>>30</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                    <div class="form-group ms-2">
                        <label for="colsSelect" class="me-2">Columns:</label>
                        <select id="colsSelect" name="cols" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="2" <?php echo $cols == 2 ? 'selected' : ''; ?>>2</option>
                            <option value="3" <?php echo $cols == 3 ? 'selected' : ''; ?>>3</option>
                            <option value="4" <?php echo $cols == 4 ? 'selected' : ''; ?>>4</option>
                            <option value="6" <?php echo $cols == 6 ? 'selected' : ''; ?>>6</option>
                        </select>
                    </div>

                    <!-- Hidden inputs for existing page parameter -->
                    <?php if (isset($_GET['page'])): ?>
                        <input type="hidden" name="page" value="<?php echo $page; ?>">
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php if (empty($images)): ?>
            <div class="alert alert-info">No images found in the gallery. Start by adding some!</div>
        <?php else: ?> <div class="row" data-masonry='{"percentPosition": true, "itemSelector": ".masonry-item", "columnWidth": ".masonry-sizer"}'>
                <div class="masonry-sizer col-md-<?php echo $colClassMd; ?> col-lg-<?php echo $colClassLg; ?>"></div>
                <?php foreach ($images as $image): ?>
                    <div class="masonry-item col-md-<?php echo $colClassMd; ?> col-lg-<?php echo $colClassLg; ?>">
                        <div class="masonry-content">
                            <div class="image-actions">
                                <div class="dropdown">
                                    <button class="btn btn-sm rounded-circle" type="button" id="dropdownMenu<?php echo $image['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                            <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu<?php echo $image['id']; ?>">
                                        <li>
                                            <h6 class="dropdown-header"><?php echo htmlspecialchars($image['caption_short']); ?></h6>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <div class="px-3 py-1">
                                                <?php if (!empty($image['context'])): ?>
                                                    <p class="text-muted mb-1">Context:</p>
                                                    <p class="small mb-2"><?php echo htmlspecialchars($image['context']); ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($image['caption_long'])): ?>
                                                    <p class="text-muted mb-1">Full Caption:</p>
                                                    <p class="small mb-2"><?php echo nl2br(htmlspecialchars($image['caption_long'])); ?></p>
                                                <?php endif; ?>

                                                <?php if (!empty($image['caption_tags'])): ?>
                                                    <p class="text-muted mb-1">Tags:</p>
                                                    <p class="mb-2">
                                                        <?php
                                                        $tags = explode(',', $image['caption_tags']);
                                                        foreach ($tags as $tag) {
                                                            $tag = trim($tag);
                                                            if (!empty($tag)) {
                                                                echo '<span class="image-tag">' . htmlspecialchars($tag) . '</span>';
                                                            }
                                                        }
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="d-flex justify-content-between mt-3">
                                                    <a href="view.php?id=<?php echo $image['id']; ?>" class="label">Details</a>
                                                    <a href="edit.php?id=<?php echo $image['id']; ?>" class="label">Edit</a>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <a href="proxy.php?url=<?= urlencode($image['url']) ?>"
                                data-lightbox="gallery"
                                data-title="<?php echo htmlspecialchars($image['caption_short'] ?? ''); ?><?php echo !empty($image['caption_long']) ? ' - ' . htmlspecialchars($image['caption_long']) : ''; ?>">
                                <div class="image-container">
                                    <img src="proxy.php?url=<?= urlencode($image['url']) ?>"
                                        data-original="<?= htmlspecialchars($image['url']) ?>"
                                        class="masonry-img"
                                        alt="<?php echo htmlspecialchars($image['caption_short'] ?? 'Image'); ?>"
                                        loading="lazy">
                                </div>
                            </a>
                            <div class="image-caption">
                                <?php if (!empty($image['caption_short'])): ?>
                                    <h6><?php echo htmlspecialchars($image['caption_short']); ?></h6>
                                <?php endif; ?>
                                <?php if (!empty($image['caption_tags'])): ?>
                                    <div class="mt-1">
                                        <?php
                                        $tags = explode(',', $image['caption_tags']);
                                        foreach ($tags as $tag) {
                                            $tag = trim($tag);
                                            if (!empty($tag)) {
                                                echo '<span class="image-tag">' . htmlspecialchars($tag) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Gallery pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&cols=<?php echo $cols; ?>" tabindex="-1">Previous</a>
                        </li>

                        <?php
                        // Show limited page numbers with ellipsis
                        $start = max(1, $page - 2);
                        $end = min($start + 4, $totalPages);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&limit=' . $limit . '&cols=' . $cols . '">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                <a class="page-link" href="?page=' . $i . '&limit=' . $limit . '&cols=' . $cols . '">' . $i . '</a>
                              </li>';
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&limit=' . $limit . '&cols=' . $cols . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&cols=<?php echo $cols; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery is required for Lightbox2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Lightbox2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/js/lightbox.min.js"></script>

    <!-- Modal for Quick Add -->
    <div class="modal fade" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddModalLabel">Quick Add Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="quickAddForm">
                        <div class="mb-3">
                            <label for="imageUrl" class="form-label">Image URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="imageUrl" placeholder="https://example.com/image.jpg" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="imageContext" class="form-label">Context (optional)</label>
                            <input type="text" class="form-control" id="imageContext">
                        </div>
                        <div class="mb-3">
                            <label for="imageShortCaption" class="form-label">Short Caption (optional)</label>
                            <input type="text" class="form-control" id="imageShortCaption">
                        </div>
                        <div class="mb-3">
                            <label for="imageTags" class="form-label">Tags (optional, comma-separated)</label>
                            <input type="text" class="form-control" id="imageTags">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="quickAddSubmit">Add Image</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Check URL -->
    <div class="modal fade" id="checkUrlModal" tabindex="-1" aria-labelledby="checkUrlModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkUrlModalLabel">Check URL Duplicate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkUrlForm">
                        <div class="mb-3">
                            <label for="checkUrl" class="form-label">Image URL to Check <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="checkUrl" placeholder="https://example.com/image.jpg" required autofocus>
                            <div class="form-text">Enter the URL you want to check for duplicates</div>
                        </div>
                    </form>
                    <div id="checkResult" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="checkUrlSubmit">Check URL</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast notification for quick add result -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="resultToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
            </div>
        </div>
    </div>
    <script>
        function retryWithProxy(img) {
            if (!img.dataset.retried) {
                img.src = 'proxy.php?url=' + encodeURIComponent(img.dataset.original);
                img.dataset.retried = true;
            }
        }

        // Variable to store the highest image ID we've seen
        let highestImageId = 0;

        // Function to create HTML for a new image item
        function createImageElement(image, colClassMd, colClassLg) {
            // Create main container
            const itemDiv = document.createElement('div');
            itemDiv.className = `masonry-item col-md-${colClassMd} col-lg-${colClassLg}`;
            itemDiv.setAttribute('data-id', image.id);

            // Track highest ID
            if (parseInt(image.id) > highestImageId) {
                highestImageId = parseInt(image.id);
            }

            // Set inner HTML with proper structure
            itemDiv.innerHTML = `
                <div class="masonry-content">
                    <div class="image-actions">
                        <div class="dropdown">
                            <button class="btn btn-sm rounded-circle" type="button" id="dropdownMenu${image.id}" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                                    <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z" />
                                </svg>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu${image.id}">
                                <li>
                                    <h6 class="dropdown-header">${image.caption_short ? image.caption_short : ''}</h6>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <div class="px-3 py-1">
                                        ${image.context ? `<p class="text-muted mb-1">Context:</p><p class="small mb-2">${image.context}</p>` : ''}
                                        ${image.caption_long ? `<p class="text-muted mb-1">Full Caption:</p><p class="small mb-2">${image.caption_long}</p>` : ''}
                                        ${image.caption_tags ? `
                                            <p class="text-muted mb-1">Tags:</p>
                                            <p class="mb-2">
                                                ${image.caption_tags.split(',').map(tag => {
                                                    tag = tag.trim();
                                                    if (tag) {
                                                        return `<span class="image-tag">${tag}</span>`;
                                                    }
                                                    return '';
                                                }).join('')}
                                            </p>
                                        ` : ''}
                                        <div class="d-flex justify-content-between mt-3">
                                            <a href="view.php?id=${image.id}" class="label">Details</a>
                                            <a href="edit.php?id=${image.id}" class="label">Edit</a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="proxy.php?url=${encodeURIComponent(image.url)}"
                        data-lightbox="gallery"
                        data-title="${image.caption_short || ''}${image.caption_long ? ' - ' + image.caption_long : ''}">
                        <div class="image-container">
                            <img src="proxy.php?url=${encodeURIComponent(image.url)}"
                                data-original="${image.url}"
                                class="masonry-img"
                                alt="${image.caption_short || 'Image'}"
                                loading="lazy">
                        </div>
                    </a>
                    <div class="image-caption">
                        ${image.caption_short ? `<h6>${image.caption_short}</h6>` : ''}
                        ${image.caption_tags ? `
                            <div class="mt-1">
                                ${image.caption_tags.split(',').map(tag => {
                                    tag = tag.trim();
                                    if (tag) {
                                        return `<span class="image-tag">${tag}</span>`;
                                    }
                                    return '';
                                }).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;

            return itemDiv;
        }

        // Function to check for new images
        function checkForNewImages() {
            fetch(`api/get_latest.php?last_id=${highestImageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.images && data.images.length > 0) {
                        const grid = document.querySelector('.row[data-masonry]');
                        const colClassMd = document.querySelector('.masonry-sizer').classList[1].split('-')[2];
                        const colClassLg = document.querySelector('.masonry-sizer').classList[2].split('-')[2];

                        // Get reference to the first element in the grid (after sizer)
                        const firstElement = grid.querySelector('.masonry-item');

                        // Update total count badge
                        const countBadge = document.querySelector('.badge.bg-secondary');
                        if (countBadge) {
                            countBadge.textContent = `${data.total_count} images in gallery`;
                        }

                        // Add new images to the grid
                        data.images.forEach(image => {
                            // Check if this image already exists in the DOM
                            if (!document.querySelector(`.masonry-item[data-id="${image.id}"]`)) {
                                const itemElement = createImageElement(image, colClassMd, colClassLg);

                                // Insert at the beginning (newest first)
                                if (firstElement) {
                                    grid.insertBefore(itemElement, firstElement);
                                } else {
                                    grid.appendChild(itemElement);
                                }

                                // Show a notification for new images
                                document.getElementById('toastTitle').textContent = 'New Image';
                                document.getElementById('toastMessage').textContent = 'New image added to gallery';
                                document.getElementById('toastMessage').classList.add('text-info');
                                document.getElementById('toastMessage').classList.remove('text-danger', 'text-success');

                                // Show toast notification
                                const resultToast = new bootstrap.Toast(document.getElementById('resultToast'));
                                resultToast.show();
                            }
                        });

                        // Reload masonry layout
                        if (data.images.length > 0) {
                            const imgElements = document.querySelectorAll('.masonry-item img');

                            // Wait for all new images to load before updating masonry
                            imagesLoaded(grid).on('always', function() {
                                const msnry = Masonry.data(grid);
                                if (msnry) {
                                    msnry.reloadItems();
                                    msnry.layout();
                                }

                                // Initialize lightbox for new images
                                if (typeof lightbox !== 'undefined') {
                                    lightbox.option({
                                        'resizeDuration': 300,
                                        'wrapAround': true,
                                        'albumLabel': 'Image %1 of %2',
                                        'fadeDuration': 300,
                                        'positionFromTop': 50,
                                        'maxWidth': 1000,
                                        'maxHeight': 800,
                                        'alwaysShowNavOnTouchDevices': true
                                    });
                                }
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking for new images:', error);
                });
        }

        // Quick Add Button Functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Find highest current image ID when page loads
            document.querySelectorAll('.masonry-item').forEach(item => {
                const id = parseInt(item.getAttribute('data-id') || 0);
                if (id > highestImageId) {
                    highestImageId = id;
                }
            });
            const quickAddBtn = document.getElementById('quickAddBtn');
            const quickAddModal = new bootstrap.Modal(document.getElementById('quickAddModal'));
            const quickAddSubmit = document.getElementById('quickAddSubmit');
            const resultToast = new bootstrap.Toast(document.getElementById('resultToast'));

            // Check URL Button and Modal
            const checkUrlBtn = document.getElementById('checkUrlBtn');
            const checkUrlModal = new bootstrap.Modal(document.getElementById('checkUrlModal'));
            const checkUrlSubmit = document.getElementById('checkUrlSubmit'); // Improved Masonry initialization with better proxy handling
            const grid = document.querySelector('.row[data-masonry]');
            if (grid) {
                // Handle image loading errors and proxy fallbacks better
                document.querySelectorAll('.masonry-img').forEach(img => {
                    // Add load event to trigger layout after proxy images are loaded
                    img.addEventListener('load', function() {
                        // Clean loading state
                        this.classList.remove('loading');

                        // Trigger masonry layout after a small delay
                        setTimeout(() => {
                            const msnry = Masonry.data(grid);
                            if (msnry) {
                                msnry.layout();
                            }
                        }, 100);
                    });
                });

                // Initialize Masonry with imagesLoaded for comprehensive layout handling
                imagesLoaded(grid).on('progress', function(instance, image) {
                    // Trigger layout during progressive loading
                    const msnry = Masonry.data(grid);
                    if (msnry) {
                        msnry.layout();
                    }
                });

                // Final layout pass after all images are loaded
                imagesLoaded(grid).on('always', function() {
                    setTimeout(() => {
                        const msnry = Masonry.data(grid);
                        if (msnry) {
                            msnry.layout();
                        }
                    }, 500);
                });
            }

            // Check URL Button
            checkUrlBtn.addEventListener('click', function() {
                checkUrlModal.show();
                // Clear previous results
                document.getElementById('checkResult').innerHTML = '';
                // Focus on URL input after modal is shown
                setTimeout(() => {
                    document.getElementById('checkUrl').focus();
                }, 500);
            });

            // Check URL Submit
            checkUrlSubmit.addEventListener('click', function() {
                const url = document.getElementById('checkUrl').value.trim();
                const resultDiv = document.getElementById('checkResult');

                if (!url) {
                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a URL to check.</div>';
                    return;
                }

                // Show loading state
                resultDiv.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Checking URL...</div>';

                // Check for duplicate
                fetch('api/check_duplicate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            url: url
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            // URL exists
                            resultDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <h6><i class="bi bi-exclamation-triangle"></i> URL Already Exists!</h6>
                                <p class="mb-2">This image URL is already in the gallery:</p>
                                <div class="border rounded p-2 bg-light">
                                    <strong>ID:</strong> #${data.image.id}<br>
                                    <strong>Caption:</strong> ${data.image.caption_short || 'No caption'}<br>
                                    <strong>Added:</strong> ${new Date(data.image.created_at).toLocaleDateString()}
                                </div>
                                <div class="mt-2">
                                    <a href="view.php?id=${data.image.id}" class="btn btn-sm btn-outline-primary" target="_blank">View Image</a>
                                    <a href="edit.php?id=${data.image.id}" class="btn btn-sm btn-outline-secondary" target="_blank">Edit Image</a>
                                </div>
                            </div>
                        `;
                        } else {
                            // URL is available
                            resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle"></i> URL Available!</h6>
                                <p class="mb-0">This URL is not in the gallery yet. You can add it safely.</p>
                            </div>
                        `;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking URL:', error);
                        resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-x-circle"></i> Error</h6>
                            <p class="mb-0">Failed to check URL. Please try again.</p>
                        </div>
                    `;
                    });
            });

            // Simple Quick Add Button
            quickAddBtn.addEventListener('click', function() {
                quickAddModal.show();
                // Tunggu sejenak agar modal selesai ditampilkan baru set fokus
                setTimeout(() => {
                    document.getElementById('imageUrl').focus();
                }, 500);
            });

            // Submit form button
            quickAddSubmit.addEventListener('click', function() {
                const url = document.getElementById('imageUrl').value.trim();
                if (!url) {
                    alert('Image URL is required');
                    return;
                }

                // Get other form values
                const context = document.getElementById('imageContext').value.trim();
                const caption_short = document.getElementById('imageShortCaption').value.trim();
                const caption_tags = document.getElementById('imageTags').value.trim();

                // Send data to server
                fetch('api/add.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            url: url,
                            context: context,
                            caption_short: caption_short,
                            caption_tags: caption_tags
                        })
                    })
                    .then(response => response.json()).then(data => {
                        // Show success message
                        document.getElementById('toastTitle').textContent = 'Success';
                        document.getElementById('toastMessage').textContent = 'Image added successfully';
                        document.getElementById('toastMessage').classList.add('text-success');
                        document.getElementById('toastMessage').classList.remove('text-danger');

                        // Reset form
                        document.getElementById('quickAddForm').reset();

                        // Close modal
                        quickAddModal.hide();

                        // Show toast notification
                        resultToast.show();

                        // Instead of reloading, fetch the new image and add it to the grid
                        if (data && data.id) {
                            // Update the highest ID if needed
                            if (parseInt(data.id) > highestImageId) {
                                highestImageId = parseInt(data.id);
                            }

                            // Fetch the newly added image and any other recent images
                            checkForNewImages();
                        }
                    }).catch(error => {
                        // Show error message
                        console.error('Error:', error);
                        document.getElementById('toastTitle').textContent = 'Error';
                        document.getElementById('toastMessage').textContent = 'Failed to add image. Check console for details.';
                        document.getElementById('toastMessage').classList.add('text-danger');
                        document.getElementById('toastMessage').classList.remove('text-success');

                        // Show toast notification
                        resultToast.show();
                    });
            });

            // Simple input alert method - alternative quick add
            document.addEventListener('keydown', function(e) {
                // Ctrl+Q shortcut to use modal instead of alerts
                if (e.ctrlKey && e.key === 'q') {
                    e.preventDefault();

                    // Clear any previous values
                    document.getElementById('quickAddForm').reset();

                    // Show modal
                    quickAddModal.show();

                    // Wait for modal to be fully displayed before focusing
                    setTimeout(() => {
                        document.getElementById('imageUrl').focus();
                    }, 500);
                }
            }); // Initialize WebSocket for real-time updates
            initWebSocket();
        }); // Set up Lightbox options when jQuery is ready

        $(document).ready(function() {
            // Configure Lightbox after it's been loaded
            if (typeof lightbox !== 'undefined') {
                lightbox.option({
                    'resizeDuration': 300,
                    'wrapAround': true,
                    'albumLabel': 'Image %1 of %2',
                    'fadeDuration': 300,
                    'positionFromTop': 50,
                    'maxWidth': 1000,
                    'maxHeight': 800,
                    'alwaysShowNavOnTouchDevices': true
                });
            }
        });

        // Function to initialize WebSocket connection
        function initWebSocket() {
            // Check if WebSocket is supported by the browser
            if ("WebSocket" in window) {
                // Connect to WebSocket server
                const ws = new WebSocket("ws://localhost:8080");

                ws.onopen = function() {
                    console.log("WebSocket connection established");
                };

                ws.onmessage = function(evt) {
                    try {
                        const data = JSON.parse(evt.data);
                        console.log("WebSocket message received:", data);
                        // Handle new image notification
                        if (data.type === "new_image" && data.image) {
                            console.log("New image notification received via WebSocket");

                            // Get grid and column classes
                            const grid = document.querySelector('.row[data-masonry]');
                            if (!grid) return;

                            const colClassMd = document.querySelector('.masonry-sizer').classList[1].split('-')[2];
                            const colClassLg = document.querySelector('.masonry-sizer').classList[2].split('-')[2];

                            // Get reference to the first element in the grid (after sizer)
                            const firstElement = grid.querySelector('.masonry-item');

                            // Create and add the new image element
                            if (!document.querySelector(`.masonry-item[data-id="${data.image.id}"]`)) {
                                const itemElement = createImageElement(data.image, colClassMd, colClassLg);

                                // Insert at the beginning (newest first)
                                if (firstElement) {
                                    grid.insertBefore(itemElement, firstElement);
                                } else {
                                    grid.appendChild(itemElement);
                                }

                                // Show notification
                                document.getElementById('toastTitle').textContent = 'New Image';
                                document.getElementById('toastMessage').textContent = 'New image added to gallery';
                                document.getElementById('toastMessage').classList.add('text-info');
                                document.getElementById('toastMessage').classList.remove('text-danger', 'text-success');

                                // Show toast notification
                                const resultToast = new bootstrap.Toast(document.getElementById('resultToast'));
                                resultToast.show();

                                // Update total count badge
                                const countBadge = document.querySelector('.badge.bg-secondary');
                                if (countBadge) {
                                    const currentCount = parseInt(countBadge.textContent);
                                    if (!isNaN(currentCount)) {
                                        countBadge.textContent = `${currentCount + 1} images in gallery`;
                                    }
                                }

                                // Wait for image to load before updating masonry
                                imagesLoaded(itemElement).on('done', function() {
                                    const msnry = Masonry.data(grid);
                                    if (msnry) {
                                        msnry.reloadItems();
                                        msnry.layout();
                                    }

                                    // Initialize lightbox for new image
                                    if (typeof lightbox !== 'undefined') {
                                        lightbox.reload();
                                    }
                                });
                            }
                        }
                    } catch (e) {
                        console.error("Error processing WebSocket message:", e);
                    }
                };

                ws.onclose = function() {
                    // Connection is closed, setup reconnection after a delay
                    console.log("WebSocket connection closed. Reconnecting in 5 seconds...");
                    setTimeout(function() {
                        initWebSocket();
                    }, 5000);
                };

                ws.onerror = function(err) {
                    console.error("WebSocket error:", err);
                    ws.close();

                    // Fallback to polling if WebSocket fails
                    console.log("WebSocket failed, falling back to polling");
                    setInterval(checkForNewImages, 5000);
                };
            } else {
                // WebSocket not supported, fallback to polling
                console.log("WebSocket not supported by your browser, falling back to polling");
                setInterval(checkForNewImages, 5000);
            }
        }
    </script>
</body>

</html>