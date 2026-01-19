{{-- resources/views/projects/partials/Show/IES/personal_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($IESpersonalInfo)
            @php
                $personalInfo = $IESpersonalInfo;
            @endphp
        @else
            @php
                $personalInfo = new \App\Models\OldProjects\IES\ProjectIESPersonalInfo();
            @endphp
        @endif

        <div class="info-grid">
            <!-- Personal Information Fields -->
            <div class="mb-3">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $personalInfo->bname ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Age:</span>
                <span class="info-value">{{ $personalInfo->age ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Gender:</span>
                <span class="info-value">{{ $personalInfo->gender ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value">{{ $personalInfo->dob ? \Carbon\Carbon::parse($personalInfo->dob)->format('d/m/Y') : 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">E-mail:</span>
                <span class="info-value">{{ $personalInfo->email ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Contact number:</span>
                <span class="info-value">{{ $personalInfo->contact ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Aadhar number:</span>
                <span class="info-value">{{ $personalInfo->aadhar ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Full Address:</span>
                <span class="info-value">{{ $personalInfo->full_address ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Name of Father:</span>
                <span class="info-value">{{ $personalInfo->father_name ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Name of Mother:</span>
                <span class="info-value">{{ $personalInfo->mother_name ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Mother tongue:</span>
                <span class="info-value">{{ $personalInfo->mother_tongue ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Current studies:</span>
                <span class="info-value">{{ $personalInfo->current_studies ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Caste:</span>
                <span class="info-value">{{ $personalInfo->bcaste ?? 'Not provided' }}</span>
            </div>
        </div>
    </div>

    <div class="card-header">
        <h4>Information about the Family</h4>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <!-- Family Information Fields -->
            <div class="mb-3">
                <span class="info-label">Occupation of Father:</span>
                <span class="info-value">{{ $personalInfo->father_occupation ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Monthly income of Father:</span>
                <span class="info-value">{{ $personalInfo->father_income ? format_indian_currency($personalInfo->father_income, 2) : 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Occupation of Mother:</span>
                <span class="info-value">{{ $personalInfo->mother_occupation ?? 'Not provided' }}</span>
            </div>

            <div class="mb-3">
                <span class="info-label">Monthly income of Mother:</span>
                <span class="info-value">{{ $personalInfo->mother_income ? format_indian_currency($personalInfo->mother_income, 2) : 'Not provided' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .form-control {
        
        color: white;
    }
    .card-header h4 {
        margin-bottom: 0;
    }
</style>
