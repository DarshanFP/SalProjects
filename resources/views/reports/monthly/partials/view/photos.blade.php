{{-- resources/views/reports/monthly/partials/view/photos.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Photos</h4>
    </div>
    <div class="card-body">
        @if($groupedPhotos && $groupedPhotos->count() > 0)
            @foreach ($groupedPhotos as $description => $photoGroup)
                <div class="mb-4 photo-group">
                    <div class="row justify-content-start">
                        @foreach ($photoGroup as $photo)
                            <div class="mb-3 col-md-4 col-sm-6 col-xs-12">
                                <div class="photo-wrapper">
                                    @php
                                        $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->photo_path);
                                    @endphp

                                    @if($fileExists)
                                        <img src="{{ asset('storage/' . $photo->photo_path) }}"
                                             alt="Photo"
                                             class="img-thumbnail"
                                             onclick="openImageModal('{{ asset('storage/' . $photo->photo_path) }}', '{{ $photo->photo_name ?? 'Photo' }}')"
                                             style="cursor: pointer;">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $photo->photo_path) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-center text-danger">
                                            <i class="mb-2 fas fa-exclamation-triangle fa-3x"></i>
                                            <p>Photo not found</p>
                                            <small>{{ $photo->photo_name ?? 'Unknown file' }}</small>
                                            <br>
                                            <small class="text-muted">
                                                Path: {{ $photo->photo_path }}<br>
                                                Exists: {{ $fileExists ? 'Yes' : 'No' }}
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-2 row">
                        <div class="col-2 report-label-col"><strong>Description:</strong></div>
                        <div class="col-10 report-value-col">{{ $description ?: 'No description provided' }}</div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-muted">No photos available.</p>
        @endif
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Photo View</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="text-center modal-body">
                <img id="modalImage" src="" alt="Photo" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal(imageSrc, imageTitle) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModalLabel').textContent = imageTitle;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}
</script>

<style>
    .photo-group {
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
    }

    .photo-wrapper {
        text-align: center;
        margin: 0 auto;
    }

    .img-thumbnail {
        max-width: 100%;
        height: auto;
        margin: 10px;
        transition: transform 0.2s;
    }

    .img-thumbnail:hover {
        transform: scale(1.05);
    }

    .photo-group:last-child {
        border-bottom: none;
    }
</style>
