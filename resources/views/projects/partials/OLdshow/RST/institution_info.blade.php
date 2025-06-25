{{-- resources/views/projects/partials/Show/RST/institution_info.blade.php --}}
{{-- <div class="mb-3 card">
    <div class="card-header">
        <h4>Institution Information</h4>
    </div>
    <div class="card-body">
        <!-- Year Setup -->
        <div class="mb-3">
            <h5>Year the Training Center was set up:</h5>
            <p>{{ $RSTInstitutionInfo?->year_setup ?? 'No data available.' }}</p>
        </div>

        <!-- Total Students Trained -->
        <div class="mb-3">
            <h5>Total Students Trained Till Date:</h5>
            <p>{{ $RSTInstitutionInfo?->total_students_trained ?? 'No data available.' }}</p>
        </div>

        <!-- Beneficiaries Last Year -->
        <div class="mb-3">
            <h5>Beneficiaries Trained in the Last Year:</h5>
            <p>{{ $RSTInstitutionInfo?->beneficiaries_last_year ?? 'No data available.' }}</p>
        </div>

        <!-- Training Outcome -->
        <div class="mb-3">
            <h5>Outcome/Impact of the Training:</h5>
            <p>{{ $RSTInstitutionInfo?->training_outcome ?? 'No data available.' }}</p>
        </div>
    </div>
</div> --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Institution Information</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Year Setup -->
            <div class="info-label"><strong>Year the Training Center was set up:</strong></div>
            <div class="info-value">{{ $RSTInstitutionInfo?->year_setup ?? 'No data available.' }}</div>

            <!-- Total Students Trained -->
            <div class="info-label"><strong>Total Students Trained Till Date:</strong></div>
            <div class="info-value">{{ $RSTInstitutionInfo?->total_students_trained ?? 'No data available.' }}</div>

            <!-- Beneficiaries Last Year -->
            <div class="info-label"><strong>Beneficiaries Trained in the Last Year:</strong></div>
            <div class="info-value">{{ $RSTInstitutionInfo?->beneficiaries_last_year ?? 'No data available.' }}</div>

            <!-- Training Outcome -->
            <div class="info-label"><strong>Outcome/Impact of the Training:</strong></div>
            <div class="info-value">{{ $RSTInstitutionInfo?->training_outcome ?? 'No data available.' }}</div>
        </div>
    </div>
</div>


