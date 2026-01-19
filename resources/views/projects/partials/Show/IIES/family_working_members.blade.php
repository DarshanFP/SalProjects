{{-- resources/views/projects/partials/Show/IIES/family_working_members.blade.php --}}
{{-- resources/views/projects/partials/show/IIES/family_working_members.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Family Working Members</h4>
    </div>
    <div class="card-body">
        @php
            // Fetch the family working members collection from the project relationship
            $familyMembers = $project->iiesFamilyWorkingMembers ?? collect([]);
        @endphp

        @if($familyMembers->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Family Member</th>
                            <th>Type/Nature of Work</th>
                            <th>Monthly Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($familyMembers as $index => $member)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $member->iies_member_name }}</td>
                                <td>{{ $member->iies_work_nature }}</td>
                                <td>{{ format_indian_currency($member->iies_monthly_income, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">No family working members recorded.</p>
        @endif
    </div>
</div>
