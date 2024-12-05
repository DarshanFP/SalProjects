<div class="mb-3 card">
    <div class="card-header">
        <h4>Target Group Annexure</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
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
                        @foreach($RSTTargetGroupAnnexure as $annexure)
                            <tr>
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
                            <td colspan="6" class="text-center">No data available for Target Group Annexure.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
