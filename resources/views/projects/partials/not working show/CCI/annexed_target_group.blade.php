{{-- resources/views/projects/partials/Show/CCI/annexed_target_group.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexed Target Group (CCI)</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #101117; color: white;">
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Date of Birth</th>
                        <th>Date of Joining</th>
                        <th>Class of Study</th>
                        <th>Family Background</th>
                    </tr>
                </thead>

                <tbody>
                    @if (isset($annexedTargetGroup) && $annexedTargetGroup->isNotEmpty())
                        @foreach ($annexedTargetGroup as $index => $group)
                            <tr>
                                <td style="text-align: center;">{{ $index + 1 }}</td>
                                <td>{{ $group->beneficiary_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($group->dob)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($group->date_of_joining)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>{{ $group->class_of_study ?? 'N/A' }}</td>
                                <td>{{ $group->family_background_description ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center">No data available for Annexed Target Group.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>
