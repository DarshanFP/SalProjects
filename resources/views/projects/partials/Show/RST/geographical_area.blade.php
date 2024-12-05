<div class="mb-3 card">
    <div class="card-header">
        <h4>Geographical Area of Beneficiaries</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Mandal</th>
                        <th>Villages</th>
                        <th>Town</th>
                        <th>No of Beneficiaries</th>
                    </tr>
                </thead>
                <tbody>
                    @if($RSTGeographicalArea && $RSTGeographicalArea->count() > 0)
                        @foreach($RSTGeographicalArea as $area)
                            <tr>
                                <td>{{ $area->mandal ?? 'N/A' }}</td>
                                <td>{{ $area->villages ?? 'N/A' }}</td>
                                <td>{{ $area->town ?? 'N/A' }}</td>
                                <td>{{ $area->no_of_beneficiaries ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center">No geographical area data recorded.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Styles for consistency -->
{{-- <style>
    .table {
        margin-bottom: 0;
    }
    .table th, .table td {
        text-align: left;
        vertical-align: middle;
    }
    .table td {
        background-color: #f9f9f9;
    }
</style> --}}
