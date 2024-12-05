<div class="mb-3 card">
    <div class="card-header">
        <h4>Number of Beneficiaries Supported this Year</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Class</th>
                        <th>Total Number</th>
                    </tr>
                </thead>
                <tbody>
                    @if($beneficiariesSupported && $beneficiariesSupported->count())
                        @foreach($beneficiariesSupported as $index => $beneficiary)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $beneficiary->class }}</td>
                                <td>{{ $beneficiary->total_number }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="text-center">No beneficiaries supported data available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
