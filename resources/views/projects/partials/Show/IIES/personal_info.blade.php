{{-- resources/views/projects/partials/Show/IIES/personal_info.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Personal Information of the Beneficiary</h4>
    </div>
    <div class="card-body">
        @php
            $personalInfo = $project->iiesPersonalInfo ?? new \App\Models\OldProjects\IIES\ProjectIIESPersonalInfo();
        @endphp

        <table class="table table-bordered">
            <tr>
                <th>Name</th>
                <td>{{ $personalInfo->iies_bname ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Age</th>
                <td>{{ $personalInfo->iies_age ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Gender</th>
                <td>{{ ucfirst($personalInfo->iies_gender ?? 'N/A') }}</td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td>{{ $personalInfo->iies_dob ? \Carbon\Carbon::parse($personalInfo->iies_dob)->format('d-m-Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td>{{ $personalInfo->iies_email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td>{{ $personalInfo->iies_contact ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Aadhar Number</th>
                <td>{{ $personalInfo->iies_aadhar ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Full Address</th>
                <td>{{ $personalInfo->iies_full_address ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="card-header">
        <h4>Family Information</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th>Father's Name</th>
                <td>{{ $personalInfo->iies_father_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Father's Occupation</th>
                <td>{{ $personalInfo->iies_father_occupation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Father's Monthly Income</th>
                <td>{{ $personalInfo->iies_father_income ? number_format($personalInfo->iies_father_income, 2) : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Mother's Name</th>
                <td>{{ $personalInfo->iies_mother_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Mother's Occupation</th>
                <td>{{ $personalInfo->iies_mother_occupation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Mother's Monthly Income</th>
                <td>{{ $personalInfo->iies_mother_income ? number_format($personalInfo->iies_mother_income, 2) : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Mother Tongue</th>
                <td>{{ $personalInfo->iies_mother_tongue ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Current Studies</th>
                <td>{{ $personalInfo->iies_current_studies ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Caste</th>
                <td>{{ $personalInfo->iies_bcaste ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
</div>

