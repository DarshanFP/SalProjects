<div class="mb-3 card">
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
</div>

<!-- Styles for Consistency -->
{{-- <style>
    .card-body p {
        background-color: #f9f9f9;
        padding: 1rem;
        border-radius: 4px;
        border: 1px solid #ddd;
        white-space: pre-wrap; /* Preserve line breaks */
    }

    h5 {
        color: #202ba3;
        font-weight: bold;
    }
</style> --}}
