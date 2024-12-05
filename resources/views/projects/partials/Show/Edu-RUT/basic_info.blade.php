<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Basic Information of Project’s Operational Area</h4>
    </div>
    <div class="card-body">
        @if($basicInfo)
            <!-- Institution Type -->
            <div class="mb-3">
                <h5>Institution Type</h5>
                <p>{{ $basicInfo->institution_type ?? 'N/A' }}</p>
            </div>

            <!-- Group Type -->
            <div class="mb-3">
                <h5>Group Type</h5>
                <p>{{ $basicInfo->group_type ?? 'N/A' }}</p>
            </div>

            <!-- Category -->
            <div class="mb-3">
                <h5>Category</h5>
                <p>{{ $basicInfo->category ?? 'N/A' }}</p>
            </div>

            <!-- Project Location -->
            <div class="mb-3">
                <h5>Project Location</h5>
                <p>{{ $basicInfo->project_location ?? 'N/A' }}</p>
            </div>

            <!-- Sisters' Work -->
            <div class="mb-3">
                <h5>Work of Sisters in the Project Area</h5>
                <p>{{ $basicInfo->sisters_work ?? 'N/A' }}</p>
            </div>

            <!-- Conditions -->
            <div class="mb-3">
                <h5>Socio-Economic and Cultural Conditions</h5>
                <p>{{ $basicInfo->conditions ?? 'N/A' }}</p>
            </div>

            <!-- Problems -->
            <div class="mb-3">
                <h5>Problems Identified and Their Consequences</h5>
                <p>{{ $basicInfo->problems ?? 'N/A' }}</p>
            </div>

            <!-- Need -->
            <div class="mb-3">
                <h5>Need of the Project</h5>
                <p>{{ $basicInfo->need ?? 'N/A' }}</p>
            </div>

            <!-- Criteria -->
            <div class="mb-3">
                <h5>Criteria for Selecting the Target Group</h5>
                <p>{{ $basicInfo->criteria ?? 'N/A' }}</p>
            </div>
        @else
            <p>No basic information available for this project.</p>
        @endif
    </div>
</div>
basicInfo
