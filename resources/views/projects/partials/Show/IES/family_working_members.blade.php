{{-- resources/views/projects/partials/Show/IES/family_working_members.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Details of Other Working Family Members</h4>
    </div>
    <div class="card-body">
        @php
            $familyMembers = $project->iesFamilyWorkingMembers ?? collect([]);
            $familyMembers = $familyMembers instanceof \Illuminate\Support\Collection ? $familyMembers : collect($familyMembers);
        @endphp

        @if($familyMembers instanceof \Illuminate\Support\Collection && $familyMembers->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sl. No</th>
                            <th>Member Name</th>
                            <th>Nature of Work</th>
                            <th>Monthly Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($familyMembers as $index => $member)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $member->member_name ?? 'N/A' }}</td>
                                <td>{{ $member->work_nature ?? 'N/A' }}</td>
                                <td>{{ format_indian_currency($member->monthly_income ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">No family members recorded.</p>
        @endif
    </div>
</div>
