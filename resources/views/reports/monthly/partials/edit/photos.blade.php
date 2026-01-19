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
                            <div class="image-preview-item" style="margin: 5px; display: flex; align-items: center;" data-photo-id="{{ $photo->photo_id }}">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" style="width: 100px; height: 100px; margin-right: 10px;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeExistingPhoto('{{ $photo->photo_id }}', '{{ $photo->photo_path }}')">Remove</button>
                            </div>
                        @endforeach
                    </div>
                    <textarea name="photo_descriptions[{{ $description }}]" class="mt-2 form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ $description }}</textarea>
                </div>
            @endforeach

            @if(empty($groupedPhotos))
                <div class="alert alert-info no-photos-message">
                    <i class="fas fa-info-circle"></i> No photos found for this report.
                </div>
            @endif

            <!-- New Photo Uploads -->
            <div id="new-photos-container">
                @php
                    $photoGroups = old('photos', [[]]);
                @endphp
                @foreach ($photoGroups as $groupIndex => $group)
                    <div class="mb-3 photo-group" data-index="{{ $groupIndex }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label">
                                    <span class="badge bg-info me-2">{{ $groupIndex + 1 }}</span>
                                    Photo Group {{ $groupIndex + 1 }} - Upload up to 3 Photos
                                </label>
                                <label class="form-label"><i>(Each photo size should be less than 5 MB)</i></label>
                            </div>
                            @if($groupIndex > 0)
                                <button type="button" class="btn btn-danger btn-sm remove-photo-group" onclick="removePhotoGroup(this)">Remove</button>
                            @endif
                        </div>
                        <div class="file-upload">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_{{ $groupIndex }}').click()">Choose up to 3 photos</button>
                            <input type="file" name="photos[{{ $groupIndex }}][]" id="photos_{{ $groupIndex }}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, {{ $groupIndex }})" style="display: none;">
                            <div id="file-text-{{ $groupIndex }}" class="file-text">No files selected</div>
                        </div>
                        <div id="photos-preview_{{ $groupIndex }}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                        <textarea name="photo_descriptions[{{ $groupIndex }}]" class="mt-2 form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ old("photo_descriptions.$groupIndex") }}</textarea>
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
    function removeExistingPhoto(photoId, photoPath) {
        if (confirm('Are you sure you want to remove this photo?')) {
            // Show loading state
            const button = event.target;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Removing...';

            // Send AJAX request to remove photo from database
            fetch(`/reports/monthly/photos/${photoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the photo element from the DOM
                    const photoElement = document.querySelector(`[data-photo-id="${photoId}"]`);
                    if (photoElement) {
                        photoElement.remove();
                    }

                    // Show success message
                    alert('Photo removed successfully!');

                    // Check if this was the last photo in the group
                    const photoGroup = document.querySelector(`[data-photo-id="${photoId}"]`)?.closest('.photo-group');
                    if (photoGroup) {
                        const remainingPhotos = photoGroup.querySelectorAll('.image-preview-item');
                        if (remainingPhotos.length === 0) {
                            // If no photos left, hide the group
                            photoGroup.style.display = 'none';
                        }
                    }

                    // Check if there are any photos left at all
                    const allPhotos = document.querySelectorAll('.image-preview-item');
                    if (allPhotos.length === 0) {
                        // Show the "no photos" message
                        const noPhotosMessage = document.querySelector('.no-photos-message');
                        if (noPhotosMessage) {
                            noPhotosMessage.style.display = 'block';
                        }
                    }
                } else {
                    alert('Error removing photo: ' + (data.message || 'Unknown error'));
                    // Reset button state
                    button.disabled = false;
                    button.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error removing photo:', error);
                alert('An error occurred while removing the photo. Please try again.');
                // Reset button state
                button.disabled = false;
                button.textContent = originalText;
            });
        }
    }

    // Add a new photo group
    function addPhotoGroup() {
        const container = document.getElementById('new-photos-container');
        const newGroupIndex = container ? container.children.length : 0;

        const newGroupHtml = `
            <div class="mb-3 photo-group" data-index="${newGroupIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <label class="form-label">
                            <span class="badge bg-info me-2">${newGroupIndex + 1}</span>
                            Photo Group ${newGroupIndex + 1} - Upload up to 3 Photos
                        </label>
                        <label class="form-label"><i>(Each photo size should be less than 5 MB)</i></label>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-photo-group" onclick="removePhotoGroup(this)">Remove</button>
                </div>
                <div class="file-upload">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_${newGroupIndex}').click()">Choose up to 3 photos</button>
                    <input type="file" name="photos[${newGroupIndex}][]" id="photos_${newGroupIndex}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, ${newGroupIndex})" style="display: none;">
                    <div id="file-text-${newGroupIndex}" class="file-text">No files selected</div>
                </div>
                <div id="photos-preview_${newGroupIndex}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                <textarea name="photo_descriptions[${newGroupIndex}]" class="mt-2 form-control auto-resize-textarea" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
            </div>
        `;

        if (container) {
            container.insertAdjacentHTML('beforeend', newGroupHtml);
        } else {
            const photosContainer = document.getElementById('photos-container');
            photosContainer.insertAdjacentHTML('beforeend', newGroupHtml);
        }

        // Initialize auto-resize for new photo group textarea using global function
        const newPhotoGroup = container ? container.lastElementChild : photosContainer.lastElementChild;
        if (newPhotoGroup && typeof initDynamicTextarea === 'function') {
            initDynamicTextarea(newPhotoGroup);
        }

        photoGroups[newGroupIndex] = [];
        reindexPhotoGroups();
    }

    function removePhotoGroup(button) {
        const photoGroup = button.closest('.photo-group');
        const groupIndex = parseInt(photoGroup.dataset.index);

        // Remove from photoGroups object
        delete photoGroups[groupIndex];

        // Remove the DOM element
        photoGroup.remove();

        // Reindex all photo groups
        reindexPhotoGroups();
    }

    function reindexPhotoGroups() {
        // Only reindex new photo groups (in new-photos-container)
        const container = document.getElementById('new-photos-container');
        if (!container) return;

        const photoGroupElements = container.querySelectorAll('.photo-group[data-index]');
        const photoGroupsArray = Array.from(photoGroupElements);

        // Store current photoGroups data by old data-index before reindexing
        const photoGroupsDataMap = {};
        photoGroupsArray.forEach((group) => {
            const oldDataIndex = parseInt(group.dataset.index);
            if (photoGroups[oldDataIndex] !== undefined) {
                photoGroupsDataMap[oldDataIndex] = photoGroups[oldDataIndex];
            }
        });

        // Clear and rebuild photoGroups object
        Object.keys(photoGroups).forEach(key => delete photoGroups[key]);

        photoGroupsArray.forEach((group, newIndex) => {
            const oldDataIndex = parseInt(group.dataset.index);

            // Update data-index
            group.dataset.index = newIndex;

            // Update badge and label
            const label = group.querySelector('label.form-label');
            if (label && label.textContent.includes('Photo Group')) {
                label.innerHTML = `<span class="badge bg-info me-2">${newIndex + 1}</span>Photo Group ${newIndex + 1} - Upload up to 3 Photos`;
            }

            // Update all IDs and names that use the index
            const fileInput = group.querySelector('input[type="file"]');
            const fileText = group.querySelector('[id^="file-text-"]');
            const fileWarning = group.querySelector('[id^="file-size-warning-"]');
            const previewContainer = group.querySelector('[id^="photos-preview_"]');
            const textarea = group.querySelector('textarea[name^="photo_descriptions"]');
            const button = group.querySelector('button[onclick*="photos_"]');

            if (fileInput) {
                const newId = `photos_${newIndex}`;
                fileInput.id = newId;
                fileInput.name = `photos[${newIndex}][]`;
                fileInput.setAttribute('onchange', `previewImages(this, ${newIndex})`);

                // Update button onclick
                if (button) {
                    button.setAttribute('onclick', `document.getElementById('${newId}').click()`);
                }
            }

            if (fileText) {
                fileText.id = `file-text-${newIndex}`;
            }

            if (fileWarning) {
                fileWarning.id = `file-size-warning-${newIndex}`;
            }

            if (previewContainer) {
                previewContainer.id = `photos-preview_${newIndex}`;
            }

            if (textarea) {
                textarea.name = `photo_descriptions[${newIndex}]`;
            }

            // Update remove buttons visibility
            const removeButton = group.querySelector('.remove-photo-group');
            if (removeButton) {
                if (newIndex === 0) {
                    removeButton.classList.add('d-none');
                } else {
                    removeButton.classList.remove('d-none');
                }
            }

            // Restore photoGroups data with new index (preserve from old index)
            if (photoGroupsDataMap[oldDataIndex] !== undefined) {
                photoGroups[newIndex] = photoGroupsDataMap[oldDataIndex];
            } else {
                photoGroups[newIndex] = [];
            }

            // Re-initialize textarea auto-resize after reindexing
            if (textarea && typeof autoResizeTextarea === 'function') {
                autoResizeTextarea(textarea);
            }
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        reindexPhotoGroups();
    });
</script>
