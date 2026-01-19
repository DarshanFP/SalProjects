
{{-- resources/views/projects/partials/Show/CCI/achievements.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Achievements</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Academic Achievements -->
            <div class="info-label">Academic Achievements:</div>
            <div class="info-value">
                @if(!empty($achievements->academic_achievements))
                    <ul class="achievement-list">
                        @foreach($achievements->academic_achievements as $index => $achievement)
                            <li>{{ $index + 1 }}. {{ $achievement }}</li>
                        @endforeach
                    </ul>
                @else
                    <span class="no-data">No academic achievements recorded.</span>
                @endif
            </div>

            <!-- Sports Achievements -->
            <div class="info-label">Sports Achievements:</div>
            <div class="info-value">
                @if(!empty($achievements->sport_achievements))
                    <ul class="achievement-list">
                        @foreach($achievements->sport_achievements as $index => $achievement)
                            <li>{{ $index + 1 }}. {{ $achievement }}</li>
                        @endforeach
                    </ul>
                @else
                    <span class="no-data">No sports achievements recorded.</span>
                @endif
            </div>

            <!-- Other Achievements -->
            <div class="info-label">Other Achievements:</div>
            <div class="info-value">
                @if(!empty($achievements->other_achievements))
                    <ul class="achievement-list">
                        @foreach($achievements->other_achievements as $index => $achievement)
                            <li>{{ $index + 1 }}. {{ $achievement }}</li>
                        @endforeach
                    </ul>
                @else
                    <span class="no-data">No other achievements recorded.</span>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .achievement-list {
        padding-left: 20px;
        margin: 0;
    }

    .achievement-list li {
        margin-bottom: 5px;
    }

    .no-data {
        color: #6c757d;
    }
</style>
