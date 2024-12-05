{{-- resources/views/reports/monthly/partials/view/photos.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Photos</h4>
    </div>
    <div class="card-body">
        @foreach ($groupedPhotos as $description => $photoGroup)
            <div class="mb-4 photo-group">
                <div class="row justify-content-start">
                    @foreach ($photoGroup as $photo)
                        <div class="mb-3 col-md-4 col-sm-6 col-xs-12">
                            <div class="photo-wrapper">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Photo" class="img-thumbnail">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mb-2 row">
                    <div class="col-6"><strong>Description:</strong></div>
                    <div class="col-6">{{ $description }}</div>
                </div>

            </div>
        @endforeach
    </div>
</div>

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
        margin: 10px; /* Add margin between images */
    }

    .photo-group:last-child {
        border-bottom: none;
    }
</style>
