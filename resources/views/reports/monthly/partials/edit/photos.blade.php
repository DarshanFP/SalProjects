{{-- resources/views/reports/monthly/partials/edit/photos.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Edit Photos</h4>
    </div>
    <div class="card-body">
        <div id="photos-container">
            <!-- Loop through grouped photos -->
            @foreach ($groupedPhotos as $description => $photoGroup)
                <div class="mb-3 photo-group" data-description="{{ $description }}">
                    <label class="form-label">Photos ({{ $description }})</label>
                    <div class="photos-preview" style="display: flex; flex-wrap: wrap;">
                        @foreach ($photoGroup as $photo)
                            <div class="image-preview-item" style="margin: 5px; display: flex; align-items: center;">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" style="width: 100px; height: 100px; margin-right: 10px;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeExistingPhoto('{{ $photo->photo_id }}')">Remove</button>
                            </div>
                        @endforeach
                    </div>
                    <textarea name="photo_descriptions[{{ $description }}]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $description }}</textarea>
                </div>
            @endforeach

            <!-- New Photo Uploads -->
            <div id="new-photos-container">
                @php
                    $photoGroups = old('photos', [[]]);
                @endphp
                @foreach ($photoGroups as $groupIndex => $group)
                    <div class="mb-3 photo-group" data-index="{{ $groupIndex }}">
                        <label class="form-label">Upload up to 3 Photos</label>
                        <label class="form-label"><i>(Each photo size should be less than 5 MB)</i></label>
                        <div class="file-upload">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_{{ $groupIndex }}').click()">Choose up to 3 photos</button>
                            <input type="file" name="photos[{{ $groupIndex }}][]" id="photos_{{ $groupIndex }}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, {{ $groupIndex }})" style="display: none;">
                            <div id="file-text-{{ $groupIndex }}" class="file-text">No files selected</div>
                        </div>
                        <div id="photos-preview_{{ $groupIndex }}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                        <textarea name="photo_descriptions[{{ $groupIndex }}]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ old("photo_descriptions.$groupIndex") }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Button to add more photo groups -->
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhotoGroup()">Add More Photos</button>
    </div>
</div>

<script>
    let photoGroups = {}; // Store new photos for each group

    // Preview newly added photos
    function previewImages(input, index) {
        const files = Array.from(input.files);
        const fileText = document.getElementById(`file-text-${index}`);
        fileText.textContent = `${files.length} file(s) selected`; // Update text based on number of files selected

        photoGroups[index] = files.slice(0, 3); // Limit to 3 photos
        updatePhotosPreview(index);
    }

    // Update the preview for a photo group
    function updatePhotosPreview(index) {
        const previewContainer = document.getElementById(`photos-preview_${index}`);
        previewContainer.innerHTML = ''; // Clear existing previews

        const files = photoGroups[index] || [];

        files.forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageContainer = document.createElement('div');
                imageContainer.className = 'image-preview-item';
                imageContainer.style.display = 'flex';
                imageContainer.style.alignItems = 'center';
                imageContainer.style.margin = '5px';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.marginRight = '10px';

                const removeButton = document.createElement('button');
                removeButton.innerText = 'Remove';
                removeButton.className = 'btn btn-danger btn-sm';
                removeButton.onclick = function() {
                    files.splice(i, 1); // Remove this photo from the array
                    photoGroups[index] = files; // Update the photoGroups object
                    updatePhotosPreview(index); // Update the previews
                };

                imageContainer.appendChild(img);
                imageContainer.appendChild(removeButton);
                previewContainer.appendChild(imageContainer);
            };
            reader.readAsDataURL(file);
        });

        const input = document.getElementById(`photos_${index}`);
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    }

    // Remove existing photo via AJAX
    function removeExistingPhoto(photoId) {
        if (confirm('Are you sure you want to remove this photo?')) {
            // Send AJAX request to remove photo from database
            fetch(`/photos/${photoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      alert('Photo removed successfully.');
                      document.querySelector(`[data-photo-id="${photoId}"]`).remove();
                  } else {
                      alert('Error removing photo.');
                  }
              });
        }
    }

    // Add a new photo group
    function addPhotoGroup() {
        const container = document.getElementById('photos-container');
        const newGroupIndex = container.children.length;

        const newGroupHtml = `
            <div class="mb-3 photo-group" data-index="${newGroupIndex}">
                <label class="form-label">Upload up to 3 Photos</label>
                <label class="form-label"><i>(Each photo size should be less than 5 MB)</i></label>
                <div class="file-upload">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_${newGroupIndex}').click()">Choose up to 3 photos</button>
                    <input type="file" name="photos[${newGroupIndex}][]" id="photos_${newGroupIndex}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, ${newGroupIndex})" style="display: none;">
                    <div id="file-text-${newGroupIndex}" class="file-text">No files selected</div>
                </div>
                <div id="photos-preview_${newGroupIndex}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                <textarea name="photo_descriptions[${newGroupIndex}]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', newGroupHtml);

        photoGroups[newGroupIndex] = [];
    }
</script>
