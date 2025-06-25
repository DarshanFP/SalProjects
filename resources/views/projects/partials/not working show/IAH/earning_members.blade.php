{{-- resources/views/projects/partials/Show/IAH/earning_members.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">List of Earning Members of the Family</h4>
    </div>
    <div class="card-body">
        @if($IAHEarningMembers && $IAHEarningMembers->count())
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Family Member</th>
                            <th>Type/Nature of Work</th>
                            <th>Monthly Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($IAHEarningMembers as $member)
                            <tr>
                                <td>{{ $member->member_name ?? 'Not provided' }}</td>
                                <td>{{ $member->work_type ?? 'Not provided' }}</td>
                                <td>{{ $member->monthly_income ? '₹' . number_format($member->monthly_income, 2) : 'Not provided' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                No earning members information available.
            </div>
        @endif
    </div>
</div>
