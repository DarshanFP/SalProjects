@if($report->project_type == 'Livelihood Development Projects' && !empty($annexures))
    <div class="section-header">Annexed Target Group</div>
    <table class="details-table">
        <tr class="header-row">
            <td>Name</td>
            <td>Age</td>
            <td>Gender</td>
            <td>Address</td>
            <td>Contact</td>
        </tr>
        @foreach($annexures as $annexure)
            <tr>
                <td>{{ $annexure->name ?? 'N/A' }}</td>
                <td>{{ $annexure->age ?? 'N/A' }}</td>
                <td>{{ $annexure->gender ?? 'N/A' }}</td>
                <td>{{ $annexure->address ?? 'N/A' }}</td>
                <td>{{ $annexure->contact ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if($report->project_type == 'Institutional Ongoing Group Educational proposal' && !empty($ageProfiles))
    <div class="section-header">Age Profiles</div>
    <table class="details-table">
        <tr class="header-row">
            <td>Age Group</td>
            <td>Male Count</td>
            <td>Female Count</td>
            <td>Total</td>
        </tr>
        @foreach($ageProfiles as $profile)
            <tr>
                <td>{{ $profile->age_group ?? 'N/A' }}</td>
                <td>{{ $profile->male_count ?? 0 }}</td>
                <td>{{ $profile->female_count ?? 0 }}</td>
                <td>{{ ($profile->male_count ?? 0) + ($profile->female_count ?? 0) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if($report->project_type == 'Residential Skill Training Proposal 2' && !empty($traineeProfiles))
    <div class="section-header">Trainee Profiles</div>
    <table class="details-table">
        <tr class="header-row">
            <td>Name</td>
            <td>Age</td>
            <td>Gender</td>
            <td>Skill Area</td>
            <td>Progress</td>
        </tr>
        @foreach($traineeProfiles as $trainee)
            <tr>
                <td>{{ $trainee->name ?? 'N/A' }}</td>
                <td>{{ $trainee->age ?? 'N/A' }}</td>
                <td>{{ $trainee->gender ?? 'N/A' }}</td>
                <td>{{ $trainee->skill_area ?? 'N/A' }}</td>
                <td>{{ $trainee->progress ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if($report->project_type == 'PROJECT PROPOSAL FOR CRISIS INTERVENTION CENTER' && !empty($inmateProfiles))
    <div class="section-header">Inmate Profiles</div>
    <table class="details-table">
        <tr class="header-row">
            <td>Name</td>
            <td>Age</td>
            <td>Gender</td>
            <td>Case Type</td>
            <td>Status</td>
        </tr>
        @foreach($inmateProfiles as $inmate)
            <tr>
                <td>{{ $inmate->name ?? 'N/A' }}</td>
                <td>{{ $inmate->age ?? 'N/A' }}</td>
                <td>{{ $inmate->gender ?? 'N/A' }}</td>
                <td>{{ $inmate->case_type ?? 'N/A' }}</td>
                <td>{{ $inmate->status ?? 'N/A' }}</td>
            </tr>
        @endforeach
    </table>
@endif
