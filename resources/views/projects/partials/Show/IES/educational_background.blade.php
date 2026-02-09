{{-- resources/views/projects/partials/Show/IES/educational_background.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Educational Background / Present Education (support requested)</h4>
    </div>
    <div class="card-body">
        @php
            $educationBackground = $project->iesEducationBackground ?? null;
        @endphp

        @if($educationBackground)
            <table class="table table-bordered">
                <tr>
                    <th>Previous Class</th>
                    <td>{{ $educationBackground->previous_class ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Present Class</th>
                    <td>{{ $educationBackground->present_class ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Amount Sanctioned</th>
                    <td>{{ format_indian_currency($educationBackground->amount_sanctioned ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Amount Utilized</th>
                    <td>{{ format_indian_currency($educationBackground->amount_utilized ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Scholarship (Previous Year)</th>
                    <td>{{ format_indian_currency($educationBackground->scholarship_previous_year ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Academic Performance</th>
                    <td>{{ $educationBackground->academic_performance ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Expected Scholarship</th>
                    <td>{{ format_indian_currency($educationBackground->expected_scholarship ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Family Contribution</th>
                    <td>{{ format_indian_currency($educationBackground->family_contribution ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <th>Remarks (Reason for No Support)</th>
                    <td>{{ $educationBackground->reason_no_support ?? 'N/A' }}</td>
                </tr>
            </table>
        @else
            <p class="text-muted">No educational background recorded.</p>
        @endif
    </div>
</div>
