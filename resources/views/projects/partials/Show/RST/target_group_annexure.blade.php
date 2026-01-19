{{-- resources/views/projects/partials/Show/RST/target_group_annexure.blade.php --}}
<div class="mb-3 card">
    <div class="card-header">
        <h4>Target Group Annexure</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 5%;">S.No.</th>
                        <th>Name</th>
                        <th>Religion</th>
                        <th>Caste</th>
                        <th>Education Background</th>
                        <th>Family Situation</th>
                        <th>Paragraph</th>
                    </tr>
                </thead>
                <tbody>
                    @if($RSTTargetGroupAnnexure && $RSTTargetGroupAnnexure->isNotEmpty())
                        @foreach($RSTTargetGroupAnnexure as $index => $annexure)
                            <tr>
                                <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                <td>{{ $annexure->rst_name ?? 'N/A' }}</td>
                                <td>{{ $annexure->rst_religion ?? 'N/A' }}</td>
                                <td>{{ $annexure->rst_caste ?? 'N/A' }}</td>
                                <td>{{ $annexure->rst_education_background ?? 'N/A' }}</td>
                                <td>{{ $annexure->rst_family_situation ?? 'N/A' }}</td>
                                <td>{{ $annexure->rst_paragraph ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center">No data available for Target Group Annexure.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
