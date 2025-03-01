{{-- resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Project Area</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Project Area</th>
                        <th>Category of Beneficiary</th>
                        <th>Direct Beneficiaries</th>
                        <th>Indirect Beneficiaries</th>
                    </tr>
                </thead>
                <tbody>
                    @if($RSTBeneficiariesArea && $RSTBeneficiariesArea->isNotEmpty())
                        @foreach($RSTBeneficiariesArea as $area)
                            <tr>
                                <td>{{ $area->project_area ?? 'N/A' }}</td>
                                <td>{{ $area->category_beneficiary ?? 'N/A' }}</td>
                                <td>{{ $area->direct_beneficiaries ?? 'N/A' }}</td>
                                <td>{{ $area->indirect_beneficiaries ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center">No project area data recorded.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
