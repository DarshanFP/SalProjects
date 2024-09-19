<!-- Blade file for photo uploads with custom button -->
<div class="mb-3 card">
    <div class="card-header">
        <h4>5. Photos</h4>
    </div>
    <div class="card-body">
        <div id="photos-container">
            <div class="mb-3 photo-group" data-index="0">
                <label for="photos_0" class="form-label">Upload Photos</label>
                <div class="file-upload">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_0').click()">Choose up to 3 photos</button>
                    <input type="file" name="photos_0[]" id="photos_0" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, 0)" style="display: none;">
                    <div id="file-text-0" class="file-text">No files selected</div>
                </div>
                <div id="photos-preview_0" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                <textarea name="photo_descriptions[]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
            </div>
        </div>
        <!-- Button to add more photo groups if needed -->
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhotoGroup()">Add More Photos</button>
    </div>
</div>
<button type="submit" class="mt-3 btn btn-primary">Upload</button>

<script>
let photos = []; // Array to store up to three photos

function previewImages(input, index) {
    const files = Array.from(input.files);
    const fileText = document.getElementById(`file-text-${index}`);
    fileText.textContent = `${files.length} file(s) selected`; // Update text based on number of files selected

    // Concatenate new files to the array and keep only the last three
    photos = [...photos, ...files].slice(-3);

    updatePhotosPreview(index);
}

function updatePhotosPreview(index) {
    const previewContainer = document.getElementById(`photos-preview_${index}`);
    previewContainer.innerHTML = ''; // Clear existing previews

    photos.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageContainer = document.createElement('div');
            imageContainer.className = 'image-preview-item';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100px';
            img.style.height = '100px';
            img.style.margin = '5px';

            const removeButton = document.createElement('button');
            removeButton.innerText = 'Remove';
            removeButton.className = 'btn btn-danger btn-sm';
            removeButton.onclick = function() {
                photos.splice(i, 1); // Remove this photo from the array
                updatePhotosPreview(index); // Update the previews
            };

            imageContainer.appendChild(img);
            imageContainer.appendChild(removeButton);
            previewContainer.appendChild(imageContainer);
        };
        reader.readAsDataURL(file);
    });

    input.value = ''; // Reset the file input after updating previews
}

function addPhotoGroup() {
    const container = document.getElementById('photos-container');
    const newGroupIndex = container.children.length;
    const newGroup = document.createElement('div');
    newGroup.className = 'mb-3 photo-group';
    newGroup.dataset.index = newGroupIndex;
    newGroup.innerHTML = `
        <label for="photos_${newGroupIndex}" class="form-label">Upload Photos</label>
        <div class="file-upload">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_${newGroupIndex}').click()">Choose up to 3 photos</button>
            <input type="file" name="photos_${newGroupIndex}[]" id="photos_${newGroupIndex}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, ${newGroupIndex})" style="display: none;">
            <div id="file-text-${newGroupIndex}" class="file-text">No files selected</div>
        </div>
        <div id="photos-preview_${newGroupIndex}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
        <textarea name="photo_descriptions[]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>`;
    container.appendChild(newGroup);
}
</script>
