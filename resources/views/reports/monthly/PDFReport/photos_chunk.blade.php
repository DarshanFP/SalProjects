@if($groupedPhotos && count($groupedPhotos) > 0)
    @foreach($groupedPhotos as $category => $photos)
        <div class="photo-container avoid-break">
            @if(!$isPhotoChunk || $photoChunkIndex === 0)
                <div class="photo-category">{{ $category }}</div>
            @endif
            <div class="photo-grid">
                @foreach($photos as $photo)
                    <div class="photo-item">
                        @if($photo['file_exists'] && $photo['full_path'])
                            <img src="{{ $photo['full_path'] }}" alt="{{ $photo['photo_name'] }}" class="photo">
                        @else
                            <div style="width: 200px; height: 150px; background-color: #f8f9fa; border: 1px dashed #ddd; display: flex; align-items: center; justify-content: center; color: #666;">
                                Photo Not Found
                            </div>
                        @endif
                        <div class="photo-description">{{ $photo['photo_name'] }}</div>
                        @if($photo['description'])
                            <div class="photo-description">{{ $photo['description'] }}</div>
                        @endif
                        @if(!empty($photo['photo_location']))
                            <div class="photo-location" style="font-size: 10pt;">{{ $photo['photo_location'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endif
