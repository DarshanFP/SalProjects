<div class="mb-4 card">
    <div class="card-header">
        <h4 class="mb-0">Annexed Target Group</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Beneficiary Name</th>
                    <th>Family Background</th>
                    <th>Need of Support</th>
                </tr>
            </thead>
            <tbody>
                @if($annexedTargetGroups && $annexedTargetGroups->count() > 0)
                    @foreach($annexedTargetGroups as $index => $group)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $group->beneficiary_name ?? 'N/A' }}</td>
                            <td>{{ $group->family_background ?? 'N/A' }}</td>
                            <td>{{ $group->need_of_support ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4">No Annexed Target Group data available.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Optional Styles --}}
{{-- <style>
    .card {
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .card-header {
        background-color: #202ba3;
        color: white;
        padding: 10px;
        font-size: 18px;
        font-weight: bold;
    }
    .table-bordered {
        border: 1px solid #ddd;
        margin-bottom: 0;
    }
</style> --}}
