@if(isset($excludePhotos) && $excludePhotos)
    <div class="section-header">Photos and Documentation</div>
    <div class="no-photos">
        <p>Photos were excluded from this PDF due to file size limitations.</p>
        <p>Please view the report online to see all photos and documentation.</p>
    </div>
@elseif($groupedPhotos && count($groupedPhotos) > 0)
    <div class="section-header">Photos and Documentation</div>
    @if(isset($totalPhotos) && $totalPhotos >= 20)
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
            <p><strong>Note:</strong> Only the first 20 photos are included in this PDF due to file size limitations. Please view the report online to see all photos.</p>
        </div>
    @endif
    @foreach($groupedPhotos as $category => $photos)
        <div class="photo-container avoid-break">
            <div class="photo-category">{{ $category }}</div>
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
@else
    <div class="section-header">Photos and Documentation</div>
    <div class="no-photos">
        <p>No photos or documentation found for this report.</p>
    </div>
@endif
