{{-- resources/views/projects/partials/Show/Edu-RUT/basic_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Basic Information of Project’s Operational Area</h4>
    </div>
    <div class="card-body">
        @if($basicInfo)
            <div class="info-grid">
                <!-- Institution Type -->
                <div class="info-label"><strong>Institution Type:</strong></div>
                <div class="info-value">{{ $basicInfo->institution_type ?? 'N/A' }}</div>

                <!-- Group Type -->
                <div class="info-label"><strong>Group Type:</strong></div>
                <div class="info-value">{{ $basicInfo->group_type ?? 'N/A' }}</div>

                <!-- Category -->
                <div class="info-label"><strong>Category:</strong></div>
                <div class="info-value">{{ $basicInfo->category ?? 'N/A' }}</div>

                <!-- Project Location -->
                <div class="info-label"><strong>Project Location:</strong></div>
                <div class="info-value">{{ $basicInfo->project_location ?? 'N/A' }}</div>

                <!-- Sisters' Work -->
                <div class="info-label"><strong>Work of Sisters in the Project Area:</strong></div>
                <div class="info-value">{{ $basicInfo->sisters_work ?? 'N/A' }}</div>

                <!-- Conditions -->
                <div class="info-label"><strong>Socio-Economic and Cultural Conditions:</strong></div>
                <div class="info-value">{{ $basicInfo->conditions ?? 'N/A' }}</div>

                <!-- Problems -->
                <div class="info-label"><strong>Problems Identified and Their Consequences:</strong></div>
                <div class="info-value">{{ $basicInfo->problems ?? 'N/A' }}</div>

                <!-- Need -->
                <div class="info-label"><strong>Need of the Project:</strong></div>
                <div class="info-value">{{ $basicInfo->need ?? 'N/A' }}</div>

                <!-- Criteria -->
                <div class="info-label"><strong>Criteria for Selecting the Target Group:</strong></div>
                <div class="info-value">{{ $basicInfo->criteria ?? 'N/A' }}</div>
            </div>
        @else
            <p>No basic information available for this project.</p>
        @endif
    </div>
</div>
