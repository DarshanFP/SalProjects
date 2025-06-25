{{-- resources/views/projects/partials/Show/ILP/personal_info.blade.php --}}

{{-- <pre>{{ print_r($ILPILPPersonalInfo, return: true) }}</pre> --}}

<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @if($ILPPersonalInfo)
            @php
                $personalInfo = $ILPPersonalInfo;
            @endphp
        @else
            @php
                $personalInfo = new \App\Models\OldProjects\ILP\ProjectILPPersonalInfo();
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

            <!-- Email -->
            <div class="mb-3">
                <span class="info-label">E-mail:</span>
                <span class="info-value">{{ $personalInfo->email ?? 'Not provided' }}</span>
            </div>

            <!-- Contact Number -->
            <div class="mb-3">
                <span class="info-label">Contact number:</span>
                <span class="info-value">{{ $personalInfo->contact_no ?? 'Not provided' }}</span>
            </div>

            <!-- Aadhar ID -->
            <div class="mb-3">
                <span class="info-label">Aadhar ID number:</span>
                <span class="info-value">{{ $personalInfo->aadhar_id ?? 'Not provided' }}</span>
            </div>

            <!-- Address -->
            <div class="mb-3">
                <span class="info-label">Full Address:</span>
                <span class="info-value">{{ $personalInfo->address ?? 'Not provided' }}</span>
            </div>

            <!-- Marital Status -->
            <div class="mb-3">
                <span class="info-label">Marital Status:</span>
                <span class="info-value">{{ $personalInfo->marital_status ?? 'Not provided' }}</span>
            </div>

            <!-- Spouse Name (Only show if Married) -->
            @if(isset($personalInfo->marital_status) && $personalInfo->marital_status == 'Married')
            <div class="mb-3">
                <span class="info-label">Spouse name:</span>
                <span class="info-value">{{ $personalInfo->spouse_name ?? 'Not provided' }}</span>
            </div>
            @endif

            <!-- Occupation -->
            <div class="mb-3">
                <span class="info-label">Occupation:</span>
                <span class="info-value">{{ $personalInfo->occupation ?? 'Not provided' }}</span>
            </div>

            <!-- Family Situation -->
            <div class="mb-3">
                <span class="info-label">Present Family Situation:</span>
                <span class="info-value">{{ $personalInfo->family_situation ?? 'Not provided' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Marital Status Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const maritalStatusField = document.querySelector('#marital_status');
        const spouseNameContainer = document.getElementById('spouse_name_container');

        maritalStatusField.addEventListener('change', function () {
            if (this.value === 'Married') {
                spouseNameContainer.style.display = 'block';
            } else {
                spouseNameContainer.style.display = 'none';
            }
        });
    });
</script>
