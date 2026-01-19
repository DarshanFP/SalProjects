{{-- resources/views/projects/partials/show/IIES/scope_financial_support.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Scope of Receiving Financial Support</h4>
    </div>
    <div class="card-body">
        @php
            \Log::info('Blade Template - IIES Financial Support:', ['data' => $IIESFinancialSupport ?? 'Not Set']);
        @endphp

        @php
            $IIESFinancialSupport = $project->iiesFinancialSupport ?? new \App\Models\OldProjects\IIES\ProjectIIESScopeFinancialSupport();
        @endphp
        @if($IIESFinancialSupport)
            <table class="table table-bordered">
                <tr>
                    <th>Government Eligible Scholarship</th>
                    <td>{{ $IIESFinancialSupport->govt_eligible_scholarship ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Scholarship Amount</th>
                    <td>{{ $IIESFinancialSupport->scholarship_amt ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Other Eligible Scholarship</th>
                    <td>{{ $IIESFinancialSupport->other_eligible_scholarship ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Other Scholarship Amount</th>
                    <td>{{ $IIESFinancialSupport->other_scholarship_amt ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Family Contribution</th>
                    <td>{{ $IIESFinancialSupport->family_contrib ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Reason for No Contribution</th>
                    <td>{{ $IIESFinancialSupport->no_contrib_reason ?? 'N/A' }}</td>
                </tr>
            </table>
        @else
            <p>Financial Support details are not available.</p>
        @endif
    </div>
</div>

{{--
<style>
    .card-body {
        
        padding: 15px;
    }
    .col-md-6, .col-md-12 {
        padding: 10px;
    }
</style> --}}
