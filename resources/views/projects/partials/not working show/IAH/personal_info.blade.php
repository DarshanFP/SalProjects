{{-- resources/views/projects/partials/Show/IAH/personal_info.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($IAHPersonalInfo)
            @php
                $personalInfo = $IAHPersonalInfo;
            @endphp
        @else
            @php
                $personalInfo = new \App\Models\OldProjects\IAH\ProjectIAHPersonalInfo();
            @endphp
        @endif

        <div class="info-grid">
            <!-- Name -->
            <div class="mb-3">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $personalInfo->name ?? 'Not provided' }}</span>
            </div>

            <!-- Age -->
            <div class="mb-3">
                <span class="info-label">Age:</span>
                <span class="info-value">{{ $personalInfo->age ?? 'Not provided' }}</span>
            </div>

            <!-- Gender -->
            <div class="mb-3">
                <span class="info-label">Gender:</span>
                <span class="info-value">{{ $personalInfo->gender ?? 'Not provided' }}</span>
            </div>

            <!-- Date of Birth -->
            <div class="mb-3">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value">{{ $personalInfo->dob ? \Carbon\Carbon::parse($personalInfo->dob)->format('d/m/Y') : 'Not provided' }}</span>
            </div>

            <!-- Aadhar Number -->
            <div class="mb-3">
                <span class="info-label">Aadhar Number:</span>
                <span class="info-value">{{ $personalInfo->aadhar ?? 'Not provided' }}</span>
            </div>

            <!-- Contact Number -->
            <div class="mb-3">
                <span class="info-label">Contact Number:</span>
                <span class="info-value">{{ $personalInfo->contact ?? 'Not provided' }}</span>
            </div>

            <!-- Full Address -->
            <div class="mb-3">
                <span class="info-label">Full Address:</span>
                <span class="info-value">{{ $personalInfo->address ?? 'Not provided' }}</span>
            </div>

            <!-- E-mail -->
            <div class="mb-3">
                <span class="info-label">E-mail:</span>
                <span class="info-value">{{ $personalInfo->email ?? 'Not provided' }}</span>
            </div>

            <!-- Guardian's Name -->
            <div class="mb-3">
                <span class="info-label">Name of Father/Husband/Legal Guardian:</span>
                <span class="info-value">{{ $personalInfo->guardian_name ?? 'Not provided' }}</span>
            </div>

            <!-- Number of Children -->
            <div class="mb-3">
                <span class="info-label">Number of Children:</span>
                <span class="info-value">{{ $personalInfo->children ?? 'Not provided' }}</span>
            </div>

            <!-- Caste -->
            <div class="mb-3">
                <span class="info-label">Caste (Specify):</span>
                <span class="info-value">{{ $personalInfo->caste ?? 'Not provided' }}</span>
            </div>

            <!-- Religion -->
            <div class="mb-3">
                <span class="info-label">Religion:</span>
                <span class="info-value">{{ $personalInfo->religion ?? 'Not provided' }}</span>
            </div>
        </div>
    </div>
</div>
