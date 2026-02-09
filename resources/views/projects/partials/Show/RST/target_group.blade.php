{{-- resources/views/projects/partials/Show/RST/target_group.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Target Group</h4>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <span class="fw-bold">Number of Beneficiaries:</span>
            <span>{{ $RSTTargetGroup?->tg_no_of_beneficiaries ?? 'N/A' }}</span>
        </div>

        <div class="form-group">
            <span class="fw-bold d-block mb-2">Description of Beneficiaries' Problems:</span>
            <div style="white-space: pre-wrap; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.6;">{{ $RSTTargetGroup?->beneficiaries_description_problems ?? 'No description provided.' }}</div>
        </div>
    </div>
</div>
