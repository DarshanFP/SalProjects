{{-- resources/views/projects/partials/Show/LDP/target_group.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Annexed Target Group: Livelihood Development Projects</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead style="background-color: #202ba3; color: white;">
                    <tr>
                        <th style="text-align: center;">S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Family Situation</th>
                        <th>Nature of Livelihood</th>
                        <th>Amount Requested</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($LDPtargetGroups) && $LDPtargetGroups->isNotEmpty())
                        @foreach($LDPtargetGroups as $index => $targetGroup)
                            <tr>
                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                <td>{{ $targetGroup->L_beneficiary_name ?? 'N/A' }}</td>
                                <td>{{ $targetGroup->L_family_situation ?? 'N/A' }}</td>
                                <td>{{ $targetGroup->L_nature_of_livelihood ?? 'N/A' }}</td>
                                <td>{{ $targetGroup->L_amount_requested ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center">No target groups available.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
