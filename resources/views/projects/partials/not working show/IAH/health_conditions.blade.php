{{-- resources/views/projects/partials/Show/IAH/health_conditions.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Health Condition Details</h4>
    </div>
    <div class="card-body">
        @if($IAHHealthCondition)
            @php
                $healthCondition = $IAHHealthCondition;
            @endphp
        @else
            @php
                $healthCondition = new \App\Models\OldProjects\IAH\ProjectIAHHealthCondition();
            @endphp
        @endif

        <div class="info-grid">
            <!-- Nature of Illness -->
            <div class="mb-3">
                <span class="info-label">Nature of Illness:</span>
                <span class="info-value">{{ $healthCondition->illness ?? 'Not provided' }}</span>
            </div>

            <!-- Is the beneficiary undergoing medical treatment? -->
            <div class="mb-3">
                <span class="info-label">Is the beneficiary undergoing medical treatment?</span>
                <span class="info-value">{{ $healthCondition->treatment == '1' ? 'Yes' : ($healthCondition->treatment == '0' ? 'No' : 'Not specified') }}</span>
            </div>

            <!-- Doctor's Name -->
            @if($healthCondition->treatment == '1')
            <div class="mb-3">
                <span class="info-label">Name of Doctor:</span>
                <span class="info-value">{{ $healthCondition->doctor ?? 'Not provided' }}</span>
            </div>
            @endif

            <!-- Name of Hospital -->
            <div class="mb-3">
                <span class="info-label">Name of Hospital:</span>
                <span class="info-value">{{ $healthCondition->hospital ?? 'Not provided' }}</span>
            </div>

            <!-- Address of Doctor/Hospital -->
            <div class="mb-3">
                <span class="info-label">Address of Doctor/Hospital:</span>
                <span class="info-value">{{ $healthCondition->doctor_address ?? 'Not provided' }}</span>
            </div>

            <!-- Health Situation -->
            <div class="mb-3">
                <span class="info-label">Health situation of the beneficiary:</span>
                <span class="info-value">{{ $healthCondition->health_situation ?? 'Not provided' }}</span>
            </div>

            <!-- Family Situation -->
            <div class="mb-3">
                <span class="info-label">Present situation of the family:</span>
                <span class="info-value">{{ $healthCondition->family_situation ?? 'Not provided' }}</span>
            </div>
        </div>
    </div>
</div>
