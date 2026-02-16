{{-- resources/views/projects/partials/Show/RST/beneficiaries_area.blade.php --}}
@php
    $totalDirect = ($RSTBeneficiariesArea instanceof \Illuminate\Support\Collection && $RSTBeneficiariesArea->isNotEmpty())
        ? $RSTBeneficiariesArea->sum('direct_beneficiaries')
        : 0;
    $totalIndirect = ($RSTBeneficiariesArea instanceof \Illuminate\Support\Collection && $RSTBeneficiariesArea->isNotEmpty())
        ? $RSTBeneficiariesArea->sum('indirect_beneficiaries')
        : 0;
@endphp
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
                    @if($RSTBeneficiariesArea instanceof \Illuminate\Support\Collection && $RSTBeneficiariesArea->isNotEmpty())
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
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Total</td>
                        <td>{{ $totalDirect }}</td>
                        <td>{{ $totalIndirect }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-2">
            <strong>Total Beneficiaries:</strong> {{ $totalDirect + $totalIndirect }}
        </div>
    </div>
</div>
