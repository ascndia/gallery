/**
 * Gallery API Client
 * Simple JavaScript utility for adding images to the gallery
 */

const GalleryAPI = {
  /**
   * Add a new image to the gallery
   *
   * @param {Object} imageData - Object containing image data
   * @param {string} imageData.url - Required URL of the image
   * @param {string} [imageData.context] - Optional context information
   * @param {string} [imageData.caption_short] - Optional short caption
   * @param {string} [imageData.caption_long] - Optional long caption
   * @param {string} [imageData.caption_tags] - Optional comma-separated tags
   * @returns {Promise} - Promise resolving to the API response
   */
  addImage: function (imageData) {
    if (!imageData.url) {
      return Promise.reject(new Error("Image URL is required"));
    }

    // Get the current origin for the API endpoint
    const origin = window.location.origin;
    const apiUrl = `${origin}/api/add.php`;

    return fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(imageData),
    }).then((response) => {
      if (!response.ok) {
        return response.json().then((err) => Promise.reject(err));
      }
      return response.json();
    });
  },
};

// Example usage:
/*
GalleryAPI.addImage({
    url: 'https://example.com/image.jpg',
    context: 'Example context',
    caption_short: 'Short caption',
    caption_long: 'Longer detailed caption',
    caption_tags: 'tag1,tag2,tag3'
})
.then(response => {
    console.log('Success:', response);
})
.catch(error => {
    console.error('Error:', error);
});
*/
