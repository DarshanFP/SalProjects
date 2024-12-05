<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Basic Information of the Project</h4>
    </div>
    <div class="card-body">
        @if($basicInfo)
            <!-- Number of beneficiaries served since inception -->
            <div class="mb-3">
                <h5>Number of Beneficiaries Served Since Inception</h5>
                <p>{{ $basicInfo->number_served_since_inception ?? 'N/A' }}</p>
            </div>

            <!-- Number of beneficiaries served in the previous year -->
            <div class="mb-3">
                <h5>Number of Beneficiaries Served in the Previous Year</h5>
                <p>{{ $basicInfo->number_served_previous_year ?? 'N/A' }}</p>
            </div>

            <!-- Beneficiary categories -->
            <div class="mb-3">
                <h5>Categories of the Beneficiaries</h5>
                <p>{{ $basicInfo->beneficiary_categories ?? 'N/A' }}</p>
            </div>

            <!-- Sisters' intervention -->
            <div class="mb-3">
                <h5>Intervention of the Sisters</h5>
                <p>{{ $basicInfo->sisters_intervention ?? 'N/A' }}</p>
            </div>

            <!-- Beneficiary conditions -->
            <div class="mb-3">
                <h5>Socio-Economic and Cultural Conditions of the Beneficiaries</h5>
                <p>{{ $basicInfo->beneficiary_conditions ?? 'N/A' }}</p>
            </div>

            <!-- Beneficiary problems -->
            <div class="mb-3">
                <h5>Problems Encountered by the Beneficiaries</h5>
                <p>{{ $basicInfo->beneficiary_problems ?? 'N/A' }}</p>
            </div>

            <!-- Institution challenges -->
            <div class="mb-3">
                <h5>Challenges Faced by the Institution</h5>
                <p>{{ $basicInfo->institution_challenges ?? 'N/A' }}</p>
            </div>

            <!-- Support received -->
            <div class="mb-3">
                <h5>Support Received from Other Organizations</h5>
                <p>{{ $basicInfo->support_received ?? 'N/A' }}</p>
            </div>

            <!-- Need of the project -->
            <div class="mb-3">
                <h5>Need of the Current Project</h5>
                <p>{{ $basicInfo->project_need ?? 'N/A' }}</p>
            </div>
        @else
            <p>No Basic Information available for this project.</p>
        @endif
    </div>
</div>
