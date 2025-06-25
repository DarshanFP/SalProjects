{{-- resources/views/projects/partials/EDIT/IAH/earning_members.blade.php --}}
{{-- resources/views/projects/partials/Edit/IAH/earning_members.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Edit: List of Earning Members of the Family</h4>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Family Member</th>
                    <th>Type/Nature of Work</th>
                    <th>Monthly Income</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="earning-members-list">
                @if($project->iahEarningMembers && $project->iahEarningMembers->count())
                    @foreach($project->iahEarningMembers as $member)
                        <tr>
                            <td><input type="text" name="member_name[]" class="form-control" value="{{ old('member_name[]', $member->member_name) }}" placeholder="Enter family member's name"></td>
                            <td><input type="text" name="work_type[]" class="form-control" value="{{ old('work_type[]', $member->work_type) }}" placeholder="Enter type/nature of work"></td>
                            <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" value="{{ old('monthly_income[]', $member->monthly_income) }}" placeholder="Enter monthly income"></td>
                            <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name"></td>
                        <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work"></td>
                        <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income"></td>
                        <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
                    </tr>
                @endif
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" id="add-member">Add More</button>
    </div>
</div>

<script>
    (function(){
    document.getElementById('add-member').addEventListener('click', function () {
        const newRow = `
            <tr>
                <td><input type="text" name="member_name[]" class="form-control" placeholder="Enter family member's name"></td>
                <td><input type="text" name="work_type[]" class="form-control" placeholder="Enter type/nature of work"></td>
                <td><input type="number" step="0.01" name="monthly_income[]" class="form-control" placeholder="Enter monthly income"></td>
                <td><button type="button" class="btn btn-danger remove-member">Remove</button></td>
            </tr>
        `;
        document.getElementById('earning-members-list').insertAdjacentHTML('beforeend', newRow);
    });

    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-member')) {
            e.target.closest('tr').remove();
        }
    });
})();
</script>
