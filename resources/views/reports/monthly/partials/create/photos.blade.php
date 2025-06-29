{{-- resources/views/reports/monthly/partials/photos.blade.php --}}
<!-- Blade file for photo uploads with custom button -->

<div class="mb-3 card">
    <div class="card-header">
        <h4>Photos</h4>
    </div>
    <div class="card-body">
        <div id="photos-container">
            <!-- Existing Photo Groups -->
            @php
                $photoGroups = old('photos', [[]]);
            @endphp
            @foreach ($photoGroups as $groupIndex => $group)
                <div class="mb-3 photo-group" data-index="{{ $groupIndex }}">
                    <label class="form-label">Upload up to 3 Photos</label>
                    <label class="form-label"><i>(Each photo size should be less than 2 MB)</i></label>
                    <div class="file-upload">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_{{ $groupIndex }}').click()">Choose up to 3 photos</button>
                        <input type="file" name="photos[{{ $groupIndex }}][]" id="photos_{{ $groupIndex }}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, {{ $groupIndex }})" style="display: none;">
                        <div id="file-text-{{ $groupIndex }}" class="file-text">No files selected</div>
                        <p id="file-size-warning-{{ $groupIndex }}" style="color: red; display: none;">Each photo must be less than 2 MB!</p>
                        @if($errors->has("photos.$groupIndex"))
                            @foreach($errors->get("photos.$groupIndex.*") as $errorMessages)
                                @foreach($errorMessages as $error)
                                    <div class="text-danger">{{ $error }}</div>
                                @endforeach
                            @endforeach
                        @endif

                    </div>
                    <div id="photos-preview_{{ $groupIndex }}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                    <textarea name="photo_descriptions[{{ $groupIndex }}]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;">{{ old("photo_descriptions.$groupIndex") }}</textarea>
                    @error("photo_descriptions.$groupIndex")
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>
        <!-- Button to add more photo groups if needed -->
        <button type="button" class="mt-3 btn btn-primary" onclick="addPhotoGroup()">Add More Photos</button>
    </div>
</div>

<!-- Include your updated JavaScript here -->
<script>
    let photoGroups = {}; // Object to store photos per group

    function previewImages(input, index) {
        const files = Array.from(input.files);
        const fileText = document.getElementById(`file-text-${index}`);
        const sizeWarning = document.getElementById(`file-size-warning-${index}`);

        // Check file sizes
        const validFiles = [];
        let hasSizeError = false;

        files.forEach((file, i) => {
            if (file.size > 2097152) { // 2 MB in bytes
                hasSizeError = true;
                sizeWarning.style.display = 'block';
            } else {
                validFiles.push(file);
            }
        });

        if (hasSizeError) {
            input.value = ''; // Reset the file input
            return;
        } else {
            sizeWarning.style.display = 'none';
        }

        // Limit to 3 files per group
        photoGroups[index] = validFiles.slice(0, 3);
        fileText.textContent = `${photoGroups[index].length} file(s) selected`; // Update text based on number of files selected

        updatePhotosPreview(index);
    }

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
                    // Remove this file from the array
                    files.splice(i, 1);
                    photoGroups[index] = files;
                    updatePhotosPreview(index);
                    // Update the file input (cannot modify input.files directly)
                    // So we need to prompt the user to re-select files if they remove any
                    alert('Please re-select your files after removing one.');
                };

                imageContainer.appendChild(img);
                imageContainer.appendChild(removeButton);
                previewContainer.appendChild(imageContainer);
            };
            reader.readAsDataURL(file);
        });
    }

    function addPhotoGroup() {
        const container = document.getElementById('photos-container');
        const newGroupIndex = container.children.length;

        const newGroupHtml = `
            <div class="mb-3 photo-group" data-index="${newGroupIndex}">
                <label class="form-label">Upload up to 3 Photos</label>
                <label class="form-label"><i>(Each photo size should be less than 2 MB)</i></label>
                <div class="file-upload">
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('photos_${newGroupIndex}').click()">Choose up to 3 photos</button>
                    <input type="file" name="photos[${newGroupIndex}][]" id="photos_${newGroupIndex}" class="mb-2 form-control" accept="image/*" multiple onchange="previewImages(this, ${newGroupIndex})" style="display: none;">
                    <div id="file-text-${newGroupIndex}" class="file-text">No files selected</div>
                    <p id="file-size-warning-${newGroupIndex}" style="color: red; display: none;">Each photo must be less than 2 MB!</p>
                </div>
                <div id="photos-preview_${newGroupIndex}" class="photos-preview" style="display: flex; flex-wrap: wrap;"></div>
                <textarea name="photo_descriptions[${newGroupIndex}]" class="mt-2 form-control" rows="3" placeholder="Brief Description (WHO WHERE WHAT WHEN)" style="background-color: #202ba3;"></textarea>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', newGroupHtml);

        // Initialize the photoGroups object for the new group
        photoGroups[newGroupIndex] = [];
    }
</script>

{{-- <script>
    let photoGroups = {}; // Object to store photos per group

    function previewImages(input, index) {
        const files = Array.from(input.files);
        const fileText = document.getElementById(`file-text-${index}`);
        fileText.textContent = `${files.length} file(s) selected`; // Update text based on number of files selected

        // Limit to 3 files per group
        photoGroups[index] = files.slice(0, 3);

        updatePhotosPreview(index);
    }

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

        // Update the input files
        const input = document.getElementById(`photos_${index}`);
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
    }

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

        // Initialize the photoGroups object for the new group
        photoGroups[newGroupIndex] = [];
    }
</script> --}}
