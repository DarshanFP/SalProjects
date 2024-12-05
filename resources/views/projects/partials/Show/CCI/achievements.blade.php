<div class="mb-3 card">
    <div class="card-header">
        <h4>Achievements</h4>
    </div>
    <div class="card-body">
        <!-- Academic Achievements -->
        <div class="mb-3">
            <h5 class="achievement-heading">Academic Achievements</h5>
            <ul class="achievement-list">
                @if(!empty($achievements->academic_achievements))
                    @foreach($achievements->academic_achievements as $achievement)
                        <li>{{ $achievement }}</li>
                    @endforeach
                @else
                    <li class="no-data">No academic achievements recorded.</li>
                @endif
            </ul>
        </div>

        <!-- Sports Achievements -->
        <div class="mb-3">
            <h5 class="achievement-heading">Sports Achievements</h5>
            <ul class="achievement-list">
                @if(!empty($achievements->sport_achievements))
                    @foreach($achievements->sport_achievements as $achievement)
                        <li>{{ $achievement }}</li>
                    @endforeach
                @else
                    <li class="no-data">No sports achievements recorded.</li>
                @endif
            </ul>
        </div>

        <!-- Other Achievements -->
        <div class="mb-3">
            <h5 class="achievement-heading">Other Achievements</h5>
            <ul class="achievement-list">
                @if(!empty($achievements->other_achievements))
                    @foreach($achievements->other_achievements as $achievement)
                        <li>{{ $achievement }}</li>
                    @endforeach
                @else
                    <li class="no-data">No other achievements recorded.</li>
                @endif
            </ul>
        </div>
    </div>
</div>

<!-- Updated CSS -->
{{-- <style>
    /* Card Styles */
    .card {
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }

    .card-header {
        background-color: #202ba3;
        color: white;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
    }

    .card-header h4, .card-header h5 {
        margin: 0;
        font-size: 18px;
    }

    /* Achievement List Styles */
    .achievement-heading {
        font-weight: bold;
        color: #202ba3;
        margin-bottom: 10px;
    }

    .achievement-list {
        list-style: disc;
        margin-left: 20px;
        color: #333;
    }

    .achievement-list li {
        margin-bottom: 5px;
    }

    .achievement-list .no-data {
        color: #999;
        font-style: italic;
    }
</style> --}}
