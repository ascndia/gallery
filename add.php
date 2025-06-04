<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Image</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Gallery</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add New Image</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add New Image</h5>
            </div>
            <div class="card-body">
                <div id="messageContainer"></div> <!-- Replaced PHP message block -->

                <form id="addImageForm"> <!-- Added id, removed method="post" as JS will handle it -->
                    <div class="mb-3">
                        <label for="url" class="form-label">Image URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="url" name="url" value="" required> <!-- Cleared PHP value -->
                        <div class="form-text">Direct link to the image file</div>
                    </div>

                    <div class="mb-3">
                        <label for="context" class="form-label">Context</label>
                        <textarea class="form-control" id="context" name="context" rows="2"></textarea> <!-- Cleared PHP value -->
                        <div class="form-text">Where the image comes from or how it will be used</div>
                    </div>

                    <div class="mb-3">
                        <label for="caption_short" class="form-label">Short Caption</label>
                        <input type="text" class="form-control" id="caption_short" name="caption_short" value=""> <!-- Cleared PHP value -->
                        <div class="form-text">Brief description of the image</div>
                    </div>

                    <div class="mb-3">
                        <label for="caption_long" class="form-label">Long Caption</label>
                        <textarea class="form-control" id="caption_long" name="caption_long" rows="3"></textarea> <!-- Cleared PHP value -->
                        <div class="form-text">Detailed description of the image</div>
                    </div>

                    <div class="mb-3">
                        <label for="caption_tags" class="form-label">Tags</label>
                        <input type="text" class="form-control" id="caption_tags" name="caption_tags" value=""> <!-- Cleared PHP value -->
                        <div class="form-text">Comma-separated tags related to the image</div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Save Image</button>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addImageForm');
            const messageContainer = document.getElementById('messageContainer');

            if (form) {
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    messageContainer.innerHTML = ''; // Clear previous messages

                    const formData = new FormData(form);
                    const data = {
                        url: formData.get('url').trim(),
                        context: formData.get('context').trim(),
                        caption_short: formData.get('caption_short').trim(),
                        caption_long: formData.get('caption_long').trim(),
                        caption_tags: formData.get('caption_tags').trim()
                    };

                    if (!data.url) {
                        displayMessage('URL is required.', 'danger');
                        return;
                    }

                    try {
                        const response = await fetch('api/add.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (response.status === 201) { // Check for 201 Created
                            displayMessage(result.message || 'Image added successfully!', 'success');
                            form.reset(); // Clear the form
                        } else {
                            displayMessage(result.message || `Error: ${response.status} ${response.statusText}`, 'danger');
                        }
                    } catch (error) {
                        console.error('Error submitting form:', error);
                        displayMessage('An error occurred while submitting the form. Please check the console.', 'danger');
                    }
                });
            }

            function displayMessage(message, type) {
                messageContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
            }
        });
    </script>
</body>

</html>