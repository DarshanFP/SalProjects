{{-- resources/views/projects/partials/Show/IAH/support_details.blade.php --}}
<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Support Details</h4>
    </div>
    <div class="card-body">
        @if($IAHSupportDetails)
            @php
                $supportDetails = $IAHSupportDetails;
            @endphp
        @else
            @php
                $supportDetails = new \App\Models\OldProjects\IAH\ProjectIAHSupportDetails();
            @endphp
        @endif

        <div class="info-grid">
            <!-- Are the family members employed with St. Ann's? -->
            <div class="mb-3">
                <span class="info-label">Are the family members employed with St. Ann's?</span>
                <span class="info-value">{{ $supportDetails->employed_at_st_ann == '1' ? 'Yes' : ($supportDetails->employed_at_st_ann == '0' ? 'No' : 'Not specified') }}</span>
            </div>

            <!-- If yes, provide employment details -->
            @if($supportDetails->employed_at_st_ann == '1')
            <div class="mb-3">
                <span class="info-label">Employment details:</span>
                <span class="info-value">{{ $supportDetails->employment_details ?? 'Not provided' }}</span>
            </div>
            @endif

            <!-- Has the beneficiary or family received any kind of support from St. Ann's projects? -->
            <div class="mb-3">
                <span class="info-label">Has the beneficiary or family received any kind of support from St. Ann's projects?</span>
                <span class="info-value">{{ $supportDetails->received_support == '1' ? 'Yes' : ($supportDetails->received_support == '0' ? 'No' : 'Not specified') }}</span>
            </div>

            <!-- If yes, provide details of the support received -->
            @if($supportDetails->received_support == '1')
            <div class="mb-3">
                <span class="info-label">Details of the support received:</span>
                <span class="info-value">{{ $supportDetails->support_details ?? 'Not provided' }}</span>
            </div>
            @endif

            <!-- Does the beneficiary have access to Government or other support? -->
            <div class="mb-3">
                <span class="info-label">Does the beneficiary have access to Government or other support?</span>
                <span class="info-value">{{ $supportDetails->govt_support == '1' ? 'Yes' : ($supportDetails->govt_support == '0' ? 'No' : 'Not specified') }}</span>
            </div>

            <!-- If yes, provide the nature of the support -->
            @if($supportDetails->govt_support == '1')
            <div class="mb-3">
                <span class="info-label">Nature of the support:</span>
                <span class="info-value">{{ $supportDetails->govt_support_nature ?? 'Not provided' }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
