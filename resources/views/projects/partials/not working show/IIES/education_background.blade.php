{{-- resources/views/projects/partials/show/IIES/education_background.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Education Background</h4>
    </div>
    <div class="card-body">
        @php
            $educationBackground = $project->iiesEducationBackground ?? new \App\Models\OldProjects\IIES\ProjectIIESEducationBackground();
        @endphp

        <table class="table table-bordered">
            <tr>
                <th>Previous Education</th>
                <td>{{ $educationBackground->prev_education ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Previous Institution</th>
                <td>{{ $educationBackground->prev_institution ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Previous Institution Address</th>
                <td>{{ $educationBackground->prev_insti_address ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Percentage of Marks Secured</th>
                <td>
                    @if(isset($educationBackground->prev_marks))
                        {{ $educationBackground->prev_marks }}%
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            <tr>
                <th>Current Studies</th>
                <td>{{ $educationBackground->current_studies ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Current Institution</th>
                <td>{{ $educationBackground->curr_institution ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Current Institution Address</th>
                <td>{{ $educationBackground->curr_insti_address ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Educational Aspirations</th>
                <td>{{ $educationBackground->aspiration ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Long-term Impact of Support</th>
                <td>{{ $educationBackground->long_term_effect ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
</div>
