@if($report->outlooks && $report->outlooks->count() > 0)
    <div class="section-header">Outlooks</div>
    <table class="details-table">
        <tr class="header-row">
            <td>Outlook</td>
            <td>Description</td>
            <td>Status</td>
        </tr>
        @foreach($report->outlooks as $outlook)
            <tr>
                <td>{{ $outlook->outlook_name ?? 'N/A' }}</td>
                <td>{{ $outlook->outlook_description ?? 'N/A' }}</td>
                <td>{{ $outlook->status ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <div class="section-header">Outlooks</div>
    <p><em>No outlooks found for this report.</em></p>
@endif
